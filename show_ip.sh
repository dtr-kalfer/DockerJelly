docker ps -aq | while read id; do
  docker inspect --format '{{.Name}} - IP: {{if eq .State.Status "running"}}{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}{{else}}STOPPED{{end}} - Hostname: {{.Config.Hostname}}' "$id"
done