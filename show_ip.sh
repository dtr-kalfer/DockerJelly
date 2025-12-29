docker ps -aq | xargs -n1 docker inspect --format '{{.Name}} - IP: {{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}} - Hostname: {{.Config.Hostname}}'

