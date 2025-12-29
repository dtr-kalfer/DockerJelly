# DockerJelly ଳ

**DockerJelly** is a visual + ASCII Docker network notebook generator for humans.

It helps you map container relationships, document intent, and generate
Mermaid flowcharts — without parsing Docker internals or touching the Docker socket.

i.e. This tool will help you properly document your containers (Dockerfile, conf, sh, etc..)

> *Let’s keep those Docker ideas smooth and organized.*

---

## ✨ What problem does DockerJelly solve?

Docker setups don’t fail because of commands —  
they fail because **mental models drift**.

DockerJelly gives you:
- 🧠 mental clarity
- 🗺️ visual relationships
- 📝 per-container notes
- 🧩 a lightweight, rule-based structure

Perfect for:
- Homelabs
- Small teams
- Schools
- Legacy + modern Docker setups

---

## 🚀 Features

- ASCII tree network diagram
- Mermaid flowchart generation
- Mermaid rendered directly in-browser
- Per-container `.txt` documentation pages
- No Docker socket access required
- Works from a simple shell script output

---

## 🧪 How it works
1. Copy the show_ip.sh on your docker host (home directory).
2. sudo chmod +x show_ip.sh
3. Generate container IP + details using ./show_ip.sh:
   ```bash
   ./show_ip.sh
4. Copy / Paste the generated info. Save as: mynetwork.txt
5. Append relationship rules at the end of each line (mynetwork.txt):
   ```bash
		top1 → root container (nginx/apache)
		con_xxx → child of root
		data+con_a+con_b → database serving those apps
		side+con_db → side / failsafe container
6. You should have something similar to this:
   ```bash
		/con_proto83 - IP: 192.168.8.58 - Hostname: cff4xxxxxa84
		/con_bulletin83 - IP: 192.168.8.59 - Hostname: 90xxxxxe446e
		/con_nginx_sl - IP: 192.168.8.60 - Hostname: 91e9xxxxxc17
		/con_blogbug - IP: 192.168.8.82 - Hostname: 08b69xxxxx88
		/con_biblio_8_128 - IP: 192.168.8.88 - Hostname: xxxxxb55b90f
		/con_stray_126 - IP: 192.168.8.83 - Hostname: b0dxxxxx39fa
		/con_mysqldb - IP: 192.168.8.81 - Hostname: 0cf70xxxxxbb

## Example network:
![Homepage](./images/mynetwork.webp "DockerJelly Homepage")

7. Given the example above, we know: 
	- con_nginx_sl is the reverse proxy
	→ It is marked as the root using top1

	con_proto83 and con_bulletin83 are children of the proxy
	→ They reference con_nginx_sl

	con_bulletin83 is stateless (in our network example)
	→ It does not connect to the database

	con_proto83 requires database access
	→ It is listed inside the database rule

	con_mysqldb is the database container
	→ Declared using data+con_proto83

	con_blogbug, con_biblio_8_128, and con_stray_126:
	- Use the same database
	- Operate independently
	- Use a different tunnel / proxy
	→ Declared using side+con_mysqldb