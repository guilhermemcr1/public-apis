package main

import "testing"

func TestCIDRsAndInteger(t *testing.T) {
	if _, err := cidrs("10.0.0.0/8,192.168.0.0/16"); err != nil {
		t.Fatal(err)
	}
	if _, err := cidrs("bad"); err == nil {
		t.Fatal("invalid CIDR accepted")
	}
	t.Setenv("TEST_POSITIVE", "0")
	if _, err := integer("TEST_POSITIVE", 1); err == nil {
		t.Fatal("zero accepted")
	}
}
