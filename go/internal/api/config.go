package api

import (
	"log/slog"
	"net"
	"time"

	"github.com/galarca/public-apis/internal/geoip"
)

type Config struct {
	Logger         *slog.Logger
	Location       *time.Location
	TrustedProxies []*net.IPNet
	GetIPLimit     int
	GetUUIDLimit   int
	RateWindow     time.Duration
	Geo            *geoip.Lookup
}

func (c Config) defaults() Config {
	if c.Logger == nil {
		c.Logger = slog.Default()
	}
	if c.Location == nil {
		c.Location = time.UTC
	}
	if c.GetIPLimit < 1 {
		c.GetIPLimit = 60
	}
	if c.GetUUIDLimit < 1 {
		c.GetUUIDLimit = 60
	}
	if c.RateWindow <= 0 {
		c.RateWindow = time.Minute
	}
	if c.Geo == nil {
		c.Geo = geoip.Open("", "")
	}
	return c
}
