package api

import (
	"html/template"
	"net/http"

	"github.com/galarca/public-apis/api/openapi"
)

var hub = template.Must(template.New("hub").Parse(`<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>APIs Públicas</title></head><body><main><h1>Documentação das APIs Públicas</h1><ul><li><a href="/api/documentation/getip">Get IP</a> — <a href="/docs/getip">OpenAPI</a></li><li><a href="/api/documentation/getuuid">Get UUID</a> — <a href="/docs/getuuid">OpenAPI</a></li></ul></main></body></html>`))

func (s *service) docs(w http.ResponseWriter, r *http.Request) {
	if !allowGET(w, r, false) {
		return
	}
	w.Header().Set("Content-Type", "text/html; charset=utf-8")
	w.Header().Set("Content-Security-Policy", "default-src 'none'; style-src 'self'; frame-ancestors 'none'; base-uri 'none'")
	_ = hub.Execute(w, nil)
}
func (s *service) openapiIP(w http.ResponseWriter, r *http.Request) {
	serveOpenAPI(w, r, openapi.GetIP)
}
func (s *service) openapiUUID(w http.ResponseWriter, r *http.Request) {
	serveOpenAPI(w, r, openapi.GetUUID)
}
func serveOpenAPI(w http.ResponseWriter, r *http.Request, body []byte) {
	if !allowGET(w, r, false) {
		return
	}
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	_, _ = w.Write(body)
}
func (s *service) swagger(spec, init string) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		if !allowGET(w, r, false) {
			return
		}
		w.Header().Set("Content-Type", "text/html; charset=utf-8")
		w.Header().Set("Content-Security-Policy", "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; frame-ancestors 'none'; base-uri 'none'")
		_, _ = w.Write([]byte(`<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><link rel="stylesheet" href="/api/documentation/swagger-ui.css"><title>Swagger UI</title></head><body><div id="swagger-ui"></div><script src="/api/documentation/swagger-ui-bundle.js"></script><script src="/api/documentation/swagger-ui-standalone-preset.js"></script><script src="` + init + `"></script></body></html>`))
	}
}
func (s *service) swaggerInit(spec string) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		if !allowGET(w, r, false) {
			return
		}
		w.Header().Set("Content-Type", "application/javascript; charset=utf-8")
		_, _ = w.Write([]byte(`SwaggerUIBundle({url:"` + spec + `",dom_id:"#swagger-ui",presets:[SwaggerUIBundle.presets.apis,SwaggerUIStandalonePreset]});`))
	}
}
