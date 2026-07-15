package geoip

import (
	"net"
	"os"
	"testing"
)

func TestPublicClassificationAndUnavailableData(t *testing.T) {
	for _, value := range []string{"127.0.0.1", "10.0.0.1", "203.0.113.1", "2001:db8::1"} {
		if IsPublic(net.ParseIP(value)) {
			t.Fatalf("%s must not be public", value)
		}
	}
	if !IsPublic(net.ParseIP("8.8.8.8")) {
		t.Fatal("public address")
	}
	r := Open("", "").Lookup(net.ParseIP("8.8.8.8"), "minimal")
	if len(r.Warnings) != 2 || r.Location != nil || r.ISP != nil {
		t.Fatalf("%+v", r)
	}
}

func TestReloadWithoutDatabasesIsSafe(t *testing.T) {
	lookup := Open("", "")
	defer lookup.Close()
	if err := lookup.ReloadIfChanged(); err != nil {
		t.Fatal(err)
	}
}

func TestRealMMDBFixtures(t *testing.T) {
	city, asn := os.Getenv("GEOIP_TEST_CITY"), os.Getenv("GEOIP_TEST_ASN")
	if city == "" || asn == "" {
		t.Skip("MMDB fixtures not configured")
	}
	l := Open(city, asn)
	defer l.Close()
	r := l.Lookup(net.ParseIP("81.2.69.160"), "full")
	location, ok := r.Location.(map[string]any)
	if !ok || location["city"] != "London" {
		t.Fatalf("city: %+v", r)
	}
	r = l.Lookup(net.ParseIP("1.128.0.0"), "minimal")
	if r.Location != nil {
		t.Fatalf("missing city record must be null: %+v", r.Location)
	}
	isp, ok := r.ISP.(map[string]any)
	if !ok || isp["asn"] != uint(1221) {
		t.Fatalf("asn: %+v", r)
	}
}
