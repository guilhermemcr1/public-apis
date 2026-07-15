package api

import (
	"encoding/json"
	"fmt"
	"net/http"
	"strings"
)

func wantsJSON(r *http.Request) bool {
	return strings.EqualFold(strings.TrimSpace(r.URL.Query().Get("format")), "json")
}
func allowGET(w http.ResponseWriter, r *http.Request, getIP bool) bool {
	if r.Method == http.MethodOptions {
		w.WriteHeader(204)
		return false
	}
	if r.Method != http.MethodGet {
		writeError(w, "Method Not Allowed. Use GET.", 405, getIP && wantsJSON(r))
		return false
	}
	return true
}
func writeJSON(w http.ResponseWriter, status int, value any) {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	w.WriteHeader(status)
	_ = json.NewEncoder(w).Encode(value)
}
func writeText(w http.ResponseWriter, status int, value string) {
	w.Header().Set("Content-Type", "text/plain; charset=utf-8")
	w.WriteHeader(status)
	_, _ = w.Write([]byte(value))
}
func writeError(w http.ResponseWriter, message string, status int, getIPJSON bool) {
	if getIPJSON {
		writeJSON(w, status, map[string]any{"response_code": status, "error": message})
		return
	}
	writeJSON(w, status, map[string]any{"error": message, "status": status})
}
func writeIPError(w http.ResponseWriter, message string, status int, json bool) {
	if json {
		writeJSON(w, status, map[string]any{"response_code": status, "error": message})
	} else {
		writeText(w, status, fmt.Sprintf("Error %d: %s", status, message))
	}
}
func jsonError(w http.ResponseWriter, message string, status int, getIP bool) {
	writeError(w, message, status, getIP)
}
