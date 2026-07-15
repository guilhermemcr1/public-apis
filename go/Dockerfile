FROM golang:1.26.5-alpine AS build
WORKDIR /src/go
COPY go/go.mod go/go.sum* ./
RUN go mod download
COPY go/ ./
RUN CGO_ENABLED=0 go build -trimpath -ldflags='-s -w' -o /public-apis ./cmd/public-apis

FROM gcr.io/distroless/static-debian12:nonroot
COPY --from=build /public-apis /public-apis
EXPOSE 8080
USER nonroot:nonroot
ENTRYPOINT ["/public-apis"]
