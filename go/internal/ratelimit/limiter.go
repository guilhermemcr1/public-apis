package ratelimit

import (
	"sync"
	"time"
)

type entry struct {
	start time.Time
	count int
}

type Limiter struct {
	mu         sync.Mutex
	entries    map[string]entry
	limit      int
	window     time.Duration
	now        func() time.Time
	lastSweep  time.Time
	maxEntries int
}

func New(limit int, window time.Duration) *Limiter {
	return newWithClock(limit, window, time.Now)
}

func newWithClock(limit int, window time.Duration, now func() time.Time) *Limiter {
	if limit < 1 {
		limit = 60
	}
	if window <= 0 {
		window = time.Minute
	}
	return &Limiter{entries: make(map[string]entry), limit: limit, window: window, now: now, maxEntries: 100_000}
}

func (l *Limiter) Allow(key string) bool {
	// ponytail: one process-wide lock is sufficient for the measured target; shard only if contention is measured.
	l.mu.Lock()
	defer l.mu.Unlock()
	now := l.now()
	if l.lastSweep.IsZero() || now.Sub(l.lastSweep) >= l.window {
		for k, v := range l.entries {
			if now.Sub(v.start) >= 2*l.window {
				delete(l.entries, k)
			}
		}
		l.lastSweep = now
	}
	if _, exists := l.entries[key]; !exists && len(l.entries) >= l.maxEntries {
		return false
	}
	e := l.entries[key]
	if e.start.IsZero() || now.Sub(e.start) >= l.window {
		e = entry{start: now}
	}
	e.count++
	l.entries[key] = e
	return e.count <= l.limit
}
