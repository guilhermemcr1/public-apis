package api

import (
	"net/http"
	"strings"

	"github.com/google/uuid"
)

func (s *service) getUUID(w http.ResponseWriter, r *http.Request) {
	setUUIDHeaders(w.Header())
	if !allowGET(w, r, false) {
		return
	}
	v := strings.TrimSpace(r.URL.Query().Get("version"))
	if v != "" && v != "4" && v != "7" {
		writeError(w, "Versão de UUID inválida. Use apenas 4 ou 7.", 400, false)
		return
	}
	version := 4
	id := uuid.New()
	if v == "7" {
		var err error
		id, err = uuid.NewV7()
		if err != nil {
			writeError(w, "Internal Server Error", 500, false)
			return
		}
		version = 7
	}
	writeJSON(w, 200, map[string]any{"uuid": id.String(), "version": version})
}
