package api

import (
	"net/http"
	"time"

	"github.com/galarca/public-apis/internal/ratelimit"
)

type statusWriter struct {
	http.ResponseWriter
	status int
}

func (w *statusWriter) WriteHeader(code int) { w.status = code; w.ResponseWriter.WriteHeader(code) }
func (w *statusWriter) Write(p []byte) (int, error) {
	if w.status == 0 {
		w.status = 200
	}
	return w.ResponseWriter.Write(p)
}

func (s *service) common(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		start := time.Now()
		sw := &statusWriter{ResponseWriter: w}
		setCommonHeaders(w.Header())
		if r.ContentLength > 1024 {
			writeError(sw, "Request Entity Too Large", http.StatusRequestEntityTooLarge, false)
			return
		}
		r.Body = http.MaxBytesReader(w, r.Body, 1024)
		defer func() {
			if recover() != nil {
				jsonError(sw, "Internal Server Error", 500, false)
			}
			total := s.requests.Add(1)
			var failures uint64
			if sw.status >= 400 {
				failures = s.errors.Add(1)
			} else {
				failures = s.errors.Load()
			}
			s.log.Info("request", "method", r.Method, "path", r.URL.Path, "client_ip", clientIP(r, s.cfg.TrustedProxies).String(), "status", sw.status, "latency_ms", time.Since(start).Milliseconds(), "requests_total", total, "errors_total", failures)
		}()
		next.ServeHTTP(sw, r)
	})
}

func (s *service) limited(l *ratelimit.Limiter, getIP bool, next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		ip := clientIP(r, s.cfg.TrustedProxies)
		if !l.Allow(ip.String()) {
			if getIP {
				writeIPError(w, "Too Many Requests", 429, wantsJSON(r))
			} else {
				setUUIDHeaders(w.Header())
				writeError(w, "Too Many Requests", 429, false)
			}
			return
		}
		next(w, r)
	}
}

func setCommonHeaders(h http.Header) {
	h.Set("Access-Control-Allow-Origin", "*")
	h.Set("Access-Control-Allow-Methods", "GET, OPTIONS")
	h.Set("Access-Control-Allow-Headers", "Content-Type")
	h.Set("X-Content-Type-Options", "nosniff")
	h.Set("X-Robots-Tag", "noindex")
	h.Set("Referrer-Policy", "strict-origin-when-cross-origin")
	h.Set("Permissions-Policy", "camera=(), microphone=(), geolocation=()")
}
func setUUIDHeaders(h http.Header) {
	h.Set("Cache-Control", "no-store, no-cache, must-revalidate, max-age=0")
	h.Set("Pragma", "no-cache")
	h.Set("Expires", "0")
	h.Set("X-Frame-Options", "SAMEORIGIN")
	h.Set("Content-Security-Policy", "default-src 'none'; frame-ancestors 'none'; base-uri 'none'")
}
