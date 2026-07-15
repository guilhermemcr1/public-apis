package main

import (
	"context"
	"fmt"
	"log/slog"
	"net"
	"net/http"
	"os"
	"os/signal"
	"strconv"
	"strings"
	"syscall"
	"time"
	_ "time/tzdata"

	"github.com/galarca/public-apis/internal/api"
	"github.com/galarca/public-apis/internal/geoip"
)

func main() {
	logger := slog.New(slog.NewJSONHandler(os.Stdout, nil))
	cfg, port, err := loadConfig(logger)
	if err != nil {
		logger.Error("invalid configuration", "error", err)
		os.Exit(1)
	}
	defer cfg.Geo.Close()
	s := &http.Server{Addr: ":" + port, Handler: api.New(cfg), ReadHeaderTimeout: 5 * time.Second, ReadTimeout: 10 * time.Second, WriteTimeout: 15 * time.Second, IdleTimeout: 60 * time.Second, MaxHeaderBytes: 16 << 10}
	errs := make(chan error, 1)
	go func() { errs <- s.ListenAndServe() }()
	ctx, stop := signal.NotifyContext(context.Background(), os.Interrupt, syscall.SIGTERM)
	defer stop()
	go watchGeoIP(ctx, cfg.Geo, logger)
	select {
	case err = <-errs:
		if err != http.ErrServerClosed {
			logger.Error("server stopped", "error", err)
			os.Exit(1)
		}
		return
	case <-ctx.Done():
	}
	shutdown, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()
	if err := s.Shutdown(shutdown); err != nil {
		logger.Error("graceful shutdown", "error", err)
	}
}

func watchGeoIP(ctx context.Context, lookup *geoip.Lookup, logger *slog.Logger) {
	interval, err := time.ParseDuration(env("GEOIP_RELOAD_INTERVAL", "5m"))
	if err != nil || interval <= 0 {
		logger.Warn("invalid GEOIP_RELOAD_INTERVAL; automatic reload disabled", "value", os.Getenv("GEOIP_RELOAD_INTERVAL"))
		return
	}
	ticker := time.NewTicker(interval)
	defer ticker.Stop()
	for {
		select {
		case <-ticker.C:
			if err := lookup.ReloadIfChanged(); err != nil {
				logger.Warn("GeoIP database reload failed; keeping current readers", "error", err)
			}
		case <-ctx.Done():
			return
		}
	}
}

func loadConfig(logger *slog.Logger) (api.Config, string, error) {
	location, err := time.LoadLocation(env("APP_TIMEZONE", "America/Sao_Paulo"))
	if err != nil {
		return api.Config{}, "", fmt.Errorf("APP_TIMEZONE: %w", err)
	}
	trusted, err := cidrs(os.Getenv("TRUSTED_PROXY_CIDRS"))
	if err != nil {
		return api.Config{}, "", err
	}
	window, err := integer("RATE_WINDOW_SECONDS", 60)
	if err != nil {
		return api.Config{}, "", err
	}
	ipLimit, err := integer("GETIP_RATE_LIMIT", 60)
	if err != nil {
		return api.Config{}, "", err
	}
	uuidLimit, err := integer("GETUUID_RATE_LIMIT", 60)
	if err != nil {
		return api.Config{}, "", err
	}
	port := env("PORT", "8080")
	parsedPort, err := strconv.Atoi(port)
	if err != nil || parsedPort < 1 || parsedPort > 65535 {
		return api.Config{}, "", fmt.Errorf("PORT must be between 1 and 65535")
	}
	return api.Config{Logger: logger, Location: location, TrustedProxies: trusted, GetIPLimit: ipLimit, GetUUIDLimit: uuidLimit, RateWindow: time.Duration(window) * time.Second, Geo: geoip.Open(env("GEOIP_CITY_DATABASE_PATH", "/data/geoip/GeoLite2-City.mmdb"), env("GEOIP_ASN_DATABASE_PATH", "/data/geoip/GeoLite2-ASN.mmdb"))}, port, nil
}

func env(name, fallback string) string {
	if value := strings.TrimSpace(os.Getenv(name)); value != "" {
		return value
	}
	return fallback
}
func integer(name string, fallback int) (int, error) {
	raw := env(name, strconv.Itoa(fallback))
	value, err := strconv.Atoi(raw)
	if err != nil || value < 1 {
		return 0, fmt.Errorf("%s must be a positive integer", name)
	}
	return value, nil
}
func cidrs(raw string) ([]*net.IPNet, error) {
	if strings.TrimSpace(raw) == "" {
		return nil, nil
	}
	var out []*net.IPNet
	for _, value := range strings.Split(raw, ",") {
		_, network, err := net.ParseCIDR(strings.TrimSpace(value))
		if err != nil {
			return nil, fmt.Errorf("TRUSTED_PROXY_CIDRS: %q is invalid", value)
		}
		out = append(out, network)
	}
	return out, nil
}
