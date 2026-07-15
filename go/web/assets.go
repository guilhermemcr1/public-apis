package web

import (
	"embed"
	"io/fs"
)

//go:embed swagger-ui/*
var files embed.FS

func Swagger() fs.FS { f, _ := fs.Sub(files, "swagger-ui"); return f }
