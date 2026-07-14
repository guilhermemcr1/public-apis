package api

import (
	"encoding/json"
	"io"
	"log/slog"
	"net"
	"net/http"
	"net/http/httptest"
	"regexp"
	"strings"
	"testing"
	"time"
)

func handler(overrides ...func(*Config)) http.Handler {
	c := Config{Logger: slog.New(slog.NewTextHandler(io.Discard, nil)), Location: time.FixedZone("America/Sao_Paulo", -3*3600), GetIPLimit: 1000, GetUUIDLimit: 1000}
	for _, apply := range overrides {
		apply(&c)
	}
	return New(c)
}
func request(t *testing.T, h http.Handler, method, target, remote string, headers map[string]string) *httptest.ResponseRecorder {
	t.Helper()
	r := httptest.NewRequest(method, target, nil)
	r.RemoteAddr = remote
	for k, v := range headers {
		r.Header.Set(k, v)
	}
	w := httptest.NewRecorder()
	h.ServeHTTP(w, r)
	return w
}

func TestClientIPTrustBoundary(t *testing.T) {
	_, proxy, _ := net.ParseCIDR("10.0.0.0/8")
	r := httptest.NewRequest("GET", "/", nil)
	r.RemoteAddr = "198.51.100.7:12"
	r.Header.Set("X-Forwarded-For", "8.8.8.8")
	if got := clientIP(r, []*net.IPNet{proxy}).String(); got != "198.51.100.7" {
		t.Fatal(got)
	}
	r.RemoteAddr = "10.0.0.2:12"
	if got := clientIP(r, []*net.IPNet{proxy}).String(); got != "8.8.8.8" {
		t.Fatal(got)
	}
}

func TestGetIPContract(t *testing.T) {
	h := handler()
	remote := "8.8.8.8:123"
	if w := request(t, h, "GET", "/getip", remote, nil); w.Code != 200 || w.Body.String() != "8.8.8.8" || !strings.HasPrefix(w.Header().Get("Content-Type"), "text/plain") {
		t.Fatalf("plain: %d %q", w.Code, w.Body.String())
	}
	w := request(t, h, "GET", "/getip?format=json", remote, nil)
	var body map[string]any
	_ = json.Unmarshal(w.Body.Bytes(), &body)
	if body["version"] != "v4" || body["private"] != false || body["response_code"] != float64(200) {
		t.Fatalf("json: %s", w.Body.String())
	}
	if w = request(t, h, "GET", "/getip?ipv4&ipv6", remote, nil); w.Code != 400 || !strings.HasPrefix(w.Header().Get("Content-Type"), "text/plain") {
		t.Fatalf("conflict: %d %s", w.Code, w.Header())
	}
	if w = request(t, h, "GET", "/getip?format=json&ipv6", remote, nil); w.Code != 404 || !strings.Contains(w.Body.String(), `"response_code":404`) {
		t.Fatalf("filter: %d %s", w.Code, w.Body.String())
	}
	if w = request(t, h, "GET", "/getip?geo=1", remote, nil); w.Code != 400 || !strings.HasPrefix(w.Header().Get("Content-Type"), "text/plain") {
		t.Fatalf("geo validation: %d", w.Code)
	}
	w = request(t, h, "GET", "/getip?format=json&geo=false", remote, nil)
	if strings.Contains(w.Body.String(), `"geo"`) {
		t.Fatal("geo=false must disable geo")
	}
	w = request(t, h, "GET", "/getip?format=json&geo=full", remote, nil)
	if !strings.Contains(w.Body.String(), `"city_database_unavailable"`) {
		t.Fatal(w.Body.String())
	}
	w = request(t, h, "GET", "/getip?format=json", "203.0.113.1:1", nil)
	if !strings.Contains(w.Body.String(), `"private":true`) {
		t.Fatal(w.Body.String())
	}
}

func TestUUIDContractAndHeaders(t *testing.T) {
	h := handler()
	pattern := regexp.MustCompile(`"uuid":"[0-9a-f-]+"`)
	for _, tc := range []struct {
		target  string
		status  int
		version string
	}{{"/getuuid", 200, `"version":4`}, {"/getuuid?version=7", 200, `"version":7`}, {"/getuuid?version=9", 400, `"status":400`}} {
		w := request(t, h, "GET", tc.target, "1.1.1.1:1", nil)
		if w.Code != tc.status || !strings.Contains(w.Body.String(), tc.version) {
			t.Fatalf("%s: %d %s", tc.target, w.Code, w.Body.String())
		}
		if tc.status == 200 && !pattern.Match(w.Body.Bytes()) {
			t.Fatal(w.Body.String())
		}
		for _, name := range []string{"Cache-Control", "Content-Security-Policy", "X-Content-Type-Options"} {
			if w.Header().Get(name) == "" {
				t.Fatalf("%s missing %s", tc.target, name)
			}
		}
	}
	w := request(t, h, "POST", "/getuuid", "1.1.1.1:1", nil)
	if w.Code != 405 || w.Header().Get("Cache-Control") == "" {
		t.Fatalf("method: %d %s", w.Code, w.Header())
	}
}

func TestRateLimitsAreIndependentAndCompatible(t *testing.T) {
	h := handler(func(c *Config) { c.GetIPLimit = 1; c.GetUUIDLimit = 1; c.RateWindow = time.Hour })
	remote := "1.1.1.1:1"
	if request(t, h, "GET", "/getip", remote, nil).Code != 200 || request(t, h, "GET", "/getuuid", remote, nil).Code != 200 {
		t.Fatal("limits must be independent")
	}
	if w := request(t, h, "GET", "/getip", remote, nil); w.Code != 429 || !strings.HasPrefix(w.Header().Get("Content-Type"), "text/plain") {
		t.Fatalf("getip 429: %d %s", w.Code, w.Body.String())
	}
	if w := request(t, h, "GET", "/getuuid", remote, nil); w.Code != 429 || w.Header().Get("Cache-Control") == "" {
		t.Fatalf("uuid 429: %d", w.Code)
	}
	if request(t, h, "GET", "/docs", remote, nil).Code != 200 {
		t.Fatal("docs must not share API limit")
	}
}

func TestDocsOpenAPIAndAssets(t *testing.T) {
	h := handler()
	for _, path := range []string{"/docs", "/api/documentation", "/api/documentation/getip", "/api/documentation/getuuid", "/api/documentation/swagger-ui.css"} {
		if w := request(t, h, "GET", path, "1.1.1.1:1", nil); w.Code != 200 {
			t.Fatalf("%s: %d", path, w.Code)
		}
	}
	for _, tc := range []struct{ path, endpoint string }{{"/docs/getip", "/getip"}, {"/docs/getuuid", "/getuuid"}} {
		w := request(t, h, "GET", tc.path, "1.1.1.1:1", nil)
		var doc map[string]any
		if json.Unmarshal(w.Body.Bytes(), &doc) != nil {
			t.Fatal("invalid OpenAPI")
		}
		paths := doc["paths"].(map[string]any)
		if paths[tc.endpoint] == nil {
			t.Fatalf("%s missing", tc.endpoint)
		}
	}
}

func TestOptionsAndNotFound(t *testing.T) {
	h := handler()
	if w := request(t, h, "OPTIONS", "/getip", "1.1.1.1:1", nil); w.Code != 204 {
		t.Fatal(w.Code)
	}
	if w := request(t, h, "GET", "/missing", "1.1.1.1:1", nil); w.Code != 404 {
		t.Fatal(w.Code)
	}
}

func BenchmarkGetIPJSON(b *testing.B) {
	h := handler(func(c *Config) { c.GetIPLimit = int(^uint(0) >> 1) })
	for i := 0; i < b.N; i++ {
		r := httptest.NewRequest("GET", "/getip?format=json", nil)
		r.RemoteAddr = "8.8.8.8:1"
		w := httptest.NewRecorder()
		h.ServeHTTP(w, r)
	}
}
func BenchmarkGetUUID(b *testing.B) {
	h := handler(func(c *Config) { c.GetUUIDLimit = int(^uint(0) >> 1) })
	for i := 0; i < b.N; i++ {
		r := httptest.NewRequest("GET", "/getuuid", nil)
		r.RemoteAddr = "8.8.8.8:1"
		w := httptest.NewRecorder()
		h.ServeHTTP(w, r)
	}
}
