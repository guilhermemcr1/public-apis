package geoip

import (
	"net"

	geo "github.com/oschwald/geoip2-golang"
)

type Result struct {
	Location any
	ISP      any
	Warnings []string
}

type Lookup struct{ city, asn *geo.Reader }

func Open(cityPath, asnPath string) *Lookup {
	l := &Lookup{}
	if cityPath != "" {
		l.city, _ = geo.Open(cityPath)
	}
	if asnPath != "" {
		l.asn, _ = geo.Open(asnPath)
	}
	return l
}

func (l *Lookup) Close() {
	if l.city != nil {
		_ = l.city.Close()
	}
	if l.asn != nil {
		_ = l.asn.Close()
	}
}

func (l *Lookup) Lookup(ip net.IP, mode string) Result {
	if !IsPublic(ip) {
		return Result{}
	}
	r := Result{}
	if l.city == nil {
		r.Warnings = append(r.Warnings, "city_database_unavailable")
	} else if c, err := l.city.City(ip); err != nil {
		r.Warnings = append(r.Warnings, "city_lookup_failed")
	} else if cityEmpty(c) {
		r.Location = nil
	} else if mode == "full" {
		r.Location = full(c)
	} else {
		r.Location = minimal(c)
	}
	if l.asn == nil {
		r.Warnings = append(r.Warnings, "isp_database_unavailable")
	} else if a, err := l.asn.ASN(ip); err != nil {
		r.Warnings = append(r.Warnings, "isp_lookup_failed")
	} else if a.AutonomousSystemNumber != 0 || a.AutonomousSystemOrganization != "" {
		r.ISP = map[string]any{"asn": a.AutonomousSystemNumber, "organization": nullable(a.AutonomousSystemOrganization)}
	}
	return r
}

func cityEmpty(c *geo.City) bool {
	return c.City.GeoNameID == 0 && c.Country.GeoNameID == 0 && c.RegisteredCountry.GeoNameID == 0 && c.Location.TimeZone == "" && len(c.Subdivisions) == 0
}

func minimal(c *geo.City) map[string]any {
	var state any
	if len(c.Subdivisions) > 0 {
		s := c.Subdivisions[len(c.Subdivisions)-1]
		if s.IsoCode != "" || s.Names["en"] != "" {
			state = map[string]any{"iso_code": nullable(s.IsoCode), "name": nullable(s.Names["en"])}
		}
	}
	return map[string]any{"country": map[string]any{"iso_code": nullable(c.Country.IsoCode), "name": nullable(c.Country.Names["en"])}, "state": state, "city": nullable(c.City.Names["en"]), "postal_code": nullable(c.Postal.Code), "timezone": nullable(c.Location.TimeZone)}
}

func full(c *geo.City) map[string]any {
	var continent, subdivision any
	if c.Continent.Code != "" || c.Continent.Names["en"] != "" {
		continent = map[string]any{"code": nullable(c.Continent.Code), "name": nullable(c.Continent.Names["en"])}
	}
	if len(c.Subdivisions) > 0 {
		s := c.Subdivisions[len(c.Subdivisions)-1]
		if s.IsoCode != "" || s.Names["en"] != "" {
			subdivision = map[string]any{"iso_code": nullable(s.IsoCode), "name": nullable(s.Names["en"])}
		}
	}
	return map[string]any{"continent": continent, "country": map[string]any{"iso_code": nullable(c.Country.IsoCode), "name": nullable(c.Country.Names["en"]), "in_european_union": c.Country.IsInEuropeanUnion}, "subdivision": subdivision, "city": nullable(c.City.Names["en"]), "postal_code": nullable(c.Postal.Code), "coordinates": map[string]any{"latitude": c.Location.Latitude, "longitude": c.Location.Longitude, "accuracy_radius_km": c.Location.AccuracyRadius}, "timezone": nullable(c.Location.TimeZone)}
}

func nullable(s string) any {
	if s == "" {
		return nil
	}
	return s
}

var nonPublic = mustCIDRs("0.0.0.0/8", "10.0.0.0/8", "100.64.0.0/10", "127.0.0.0/8", "169.254.0.0/16", "172.16.0.0/12", "192.0.0.0/24", "192.0.2.0/24", "192.168.0.0/16", "198.18.0.0/15", "198.51.100.0/24", "203.0.113.0/24", "224.0.0.0/4", "240.0.0.0/4", "::/128", "::1/128", "fc00::/7", "fe80::/10", "ff00::/8", "2001:db8::/32")

func IsPublic(ip net.IP) bool {
	if ip == nil {
		return false
	}
	for _, n := range nonPublic {
		if n.Contains(ip) {
			return false
		}
	}
	return true
}
func mustCIDRs(values ...string) []*net.IPNet {
	out := make([]*net.IPNet, 0, len(values))
	for _, v := range values {
		_, n, _ := net.ParseCIDR(v)
		out = append(out, n)
	}
	return out
}
