test:
	go test ./...
race:
	go test -race ./...
vet:
	go vet ./...
bench:
	go test -run '^$$' -bench=. -benchmem ./internal/api
build:
	docker build -f Dockerfile -t public-apis-go:local ..
load:
	sh scripts/loadtest.sh
check: test race vet bench
