package api

import (
	"net/http"
	"sync/atomic"

	"github.com/galarca/public-apis/internal/ratelimit"
	"github.com/galarca/public-apis/web"
)

type service struct {
	cfg              Config
	log              interfaceLogger
	requests, errors atomic.Uint64
}
type interfaceLogger interface{ Info(string, ...any) }

func New(config Config) http.Handler {
	c := config.defaults()
	s := &service{cfg: c, log: c.Logger}
	ipLimit := ratelimit.New(c.GetIPLimit, c.RateWindow)
	uuidLimit := ratelimit.New(c.GetUUIDLimit, c.RateWindow)
	m := http.NewServeMux()
	m.HandleFunc("/", s.root)
	m.HandleFunc("/getip", s.limited(ipLimit, true, s.getIP))
	m.HandleFunc("/getuuid", s.limited(uuidLimit, false, s.getUUID))
	m.HandleFunc("/docs", s.docs)
	m.HandleFunc("/api/documentation", s.docs)
	m.HandleFunc("/docs/getip", s.openapiIP)
	m.HandleFunc("/docs/getuuid", s.openapiUUID)
	m.HandleFunc("/api/documentation/getip", s.swagger("/docs/getip", "/api/documentation/init-getip.js"))
	m.HandleFunc("/api/documentation/getuuid", s.swagger("/docs/getuuid", "/api/documentation/init-getuuid.js"))
	m.HandleFunc("/api/documentation/init-getip.js", s.swaggerInit("/docs/getip"))
	m.HandleFunc("/api/documentation/init-getuuid.js", s.swaggerInit("/docs/getuuid"))
	m.Handle("/api/documentation/", http.StripPrefix("/api/documentation/", http.FileServer(http.FS(web.Swagger()))))
	return s.common(m)
}

func (s *service) root(w http.ResponseWriter, r *http.Request) {
	if r.URL.Path != "/" {
		http.NotFound(w, r)
		return
	}
	if !allowGET(w, r, false) {
		return
	}
	writeJSON(w, 200, map[string]string{"name": "APIs Publicas", "status": "ok"})
}
