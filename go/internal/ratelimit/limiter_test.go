package ratelimit

import (
	"testing"
	"time"
)

func TestLimitResetAndCleanup(t *testing.T) {
	now := time.Unix(1, 0)
	l := newWithClock(2, time.Minute, func() time.Time { return now })
	if !l.Allow("a") || !l.Allow("a") || l.Allow("a") {
		t.Fatal("limit")
	}
	now = now.Add(time.Minute)
	if !l.Allow("a") {
		t.Fatal("reset")
	}
	now = now.Add(3 * time.Minute)
	l.Allow("b")
	if _, ok := l.entries["a"]; ok {
		t.Fatal("expired entry not removed")
	}
}

func TestEntryBound(t *testing.T) {
	l := newWithClock(1, time.Minute, time.Now)
	l.maxEntries = 1
	if !l.Allow("a") || l.Allow("b") {
		t.Fatal("entry bound")
	}
}
