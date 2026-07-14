package openapi

import _ "embed"

//go:embed getip.json
var GetIP []byte

//go:embed getuuid.json
var GetUUID []byte
