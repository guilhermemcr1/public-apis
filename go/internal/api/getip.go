package api

import (
	"fmt"
	"net"
	"net/http"
	"strings"
	"time"

	"github.com/galarca/public-apis/internal/geoip"
)

func (s *service) getIP(w http.ResponseWriter, r *http.Request) {
	if !allowGET(w, r, true) {
		return
	}
	q, js := r.URL.Query(), wantsJSON(r)
	if q.Has("ipv4") && q.Has("ipv6") {
		writeIPError(w, "Use apenas ?ipv4 ou ?ipv6, não ambos simultaneamente.", 400, js)
		return
	}
	mode := geoMode(q.Get("geo"), q.Has("geo"))
	if mode != "off" && !js {
		writeIPError(w, "O parâmetro geo exige format=json.", 400, false)
		return
	}
	ip := clientIP(r, s.cfg.TrustedProxies)
	if q.Has("ip") {
		requested := strings.TrimSpace(q.Get("ip"))
		parsed := net.ParseIP(requested)
		if requested == "" || parsed == nil {
			writeIPError(w, "O parâmetro ip deve conter um endereço IPv4 ou IPv6 válido.", 400, js)
			return
		}
		ip = parsed
		if v4 := ip.To4(); v4 != nil {
			ip = v4
		}
	}
	if ip == nil {
		ip = net.IPv4zero
	}
	version := 6
	if ip.To4() != nil {
		version = 4
	}
	if q.Has("ipv4") && version != 4 {
		writeIPError(w, fmt.Sprintf("Nenhum IPv4 detectado. IP atual: %s (v%d).", ip, version), 404, js)
		return
	}
	if q.Has("ipv6") && version != 6 {
		writeIPError(w, fmt.Sprintf("Nenhum IPv6 detectado. IP atual: %s (v%d).", ip, version), 404, js)
		return
	}
	if !js {
		writeText(w, 200, ip.String())
		return
	}
	meta := map[string]any{"api": "IP Detection API", "api_version": "1.6.0", "timestamp": time.Now().In(s.cfg.Location).Format(time.RFC3339), "server_timezone": s.cfg.Location.String()}
	payload := map[string]any{"response_code": 200, "ip": ip.String(), "version": fmt.Sprintf("v%d", version), "private": !geoip.IsPublic(ip), "meta": meta}
	if mode != "off" {
		result := s.cfg.Geo.Lookup(ip, mode)
		payload["geo"] = map[string]any{"location": result.Location, "isp": result.ISP}
		if len(result.Warnings) > 0 {
			meta["geo_warnings"] = result.Warnings
		}
	}
	writeJSON(w, 200, payload)
}

func geoMode(raw string, present bool) string {
	if !present {
		return "off"
	}
	v := strings.ToLower(strings.TrimSpace(raw))
	if v == "full" {
		return "full"
	}
	switch v {
	case "", "minimal", "min", "1", "true", "yes", "on":
		return "minimal"
	default:
		return "off"
	}
}
