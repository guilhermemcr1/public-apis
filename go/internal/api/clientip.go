package api

import (
	"net"
	"net/http"
	"strings"
)

var forwardedHeaders = []string{"CF-Connecting-IP", "X-Real-IP", "X-Forwarded-For", "X-Forwarded", "Forwarded-For"}

func clientIP(r *http.Request, trusted []*net.IPNet) net.IP {
	peer := parseRemote(r.RemoteAddr)
	if contains(trusted, peer) {
		for _, name := range forwardedHeaders {
			if ip := firstIP(r.Header.Get(name)); ip != nil {
				return ip
			}
		}
		if ip := firstIP(strings.TrimPrefix(r.Header.Get("Forwarded"), "for=")); ip != nil {
			return ip
		}
	}
	if peer == nil {
		return net.IPv4zero
	}
	return peer
}

func parseRemote(value string) net.IP {
	if host, _, err := net.SplitHostPort(value); err == nil {
		return net.ParseIP(host)
	}
	return net.ParseIP(value)
}
func firstIP(value string) net.IP {
	if value == "" {
		return nil
	}
	return net.ParseIP(strings.TrimSpace(strings.Split(value, ",")[0]))
}
func contains(nets []*net.IPNet, ip net.IP) bool {
	for _, n := range nets {
		if n.Contains(ip) {
			return true
		}
	}
	return false
}
