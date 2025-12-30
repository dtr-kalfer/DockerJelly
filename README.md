# DockerJelly ଳ

**DockerJelly** is a visual + ASCII Docker network notebook generator for humans.

It helps you map **container relationships**, **document intent**, and **generate Mermaid flowcharts** without parsing Docker internals or touching the Docker socket.

Think of it as a living **ops notebook** for your containers

> *Let’s keep those Docker ideas smooth and organized.*

---

## 🌱 Why DockerJelly Exists

Docker is powerful — but power without clarity creates friction.

Most Docker tools focus on execution: building, running, deploying.
DockerJelly focuses on **understanding**.

It exists because real-world Docker setups are often:

- Evolved over time
- Maintained by different people
- Remembered “in someone’s head”

**DockerJelly** turns that invisible knowledge into something **visible, lightweight, and human-readable**.

- Not another orchestrator.
- Not a replacement for Docker Compose or Kubernetes.
- Just a calm space to think, document, and reason about your containers.

Because **good infrastructure** starts with a **clear mental model**.

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

## 🧪 How to use
1. Copy the show_ip.sh on your docker host (home directory).
```console
chmod +x show_ip.sh
./show_ip.sh
```
	This prints container names, IP addresses, and hostnames.

2. Create your network file
```console
./show_ip.sh
```
4. Copy the output of ./show_ip.sh
5. Paste it into a text file, Save it as: mynetwork.txt

6. At the end of each line, append one rule that describes the container’s role:

| **Rules**             | **Description**           |
| ---------------------- | -------------------------------- |
| **top1** | Root container (reverse proxy / entry point) |
| **con_xxx**     | Child of the root container      |
| **data+con_a+con_b**          | Database serving specific containers |
| **side+con_db**     | Standalone / failsafe container using the DB |

6. Upload mynetwork.txt to **DockerJelly** and generate:
### ASCII diagram
![Homepage](./images/ascii_diagram.webp "DockerJelly Homepage")

### Text Generated Mermaid flowchart:

	```mermaid
	flowchart LR
	A[☁︎ con_nginx_sl<br>192.168.8.60<br>91e9xxxxxc17]
	A <---> B[🐘 con_proto83<br>192.168.8.58<br>cff4xxxxxa84]
	A <---> C[🐘 con_bulletin83<br>192.168.8.59<br>90xxxxxe446e]
	B <---> D[🗄️ con_mysqldb<br>192.168.8.81<br>0cf70xxxxxbb]
	D <---> E[⬡︎ con_blogbug<br>192.168.8.82<br>08b69xxxxx88]
	D <---> F[⬡︎ con_biblio_8_128<br>192.168.8.88<br>xxxxxb55b90f]
	D <---> G[⬡︎ con_stray_126<br>192.168.8.83<br>b0dxxxxx39fa]

*The basic text-based mermaid flowchart can be copy/pasted on the https://mermaid.live to make adjustment*
	
### Per-container note links
![Homepage](./images/per_container_notes.webp "DockerJelly Homepage")

- *The 'Save as HTML' button allows you to save a copy of the generated diagram.*

- *Add these txt files manually, use filename based on each hyperlink.*

- *Updates, docker images, .conf/.cnf/.sh, Dockerfile, bind+volume mounts..etc for each container.txt is possible, easy access by the links in html file*

> Understanding + Documentation → Confidence

## 🧪 Lets practice, using our sample network:
![Homepage](./images/mynetwork.webp "DockerJelly Homepage")

*<small>The sample network diagram is generated using dockerjelly/mermaid.js</small>*

1. Our ./show_ip.sh would look like this:
	```bash
	/con_proto83 - IP: 192.168.8.58 - Hostname: cff4xxxxxa84
	/con_bulletin83 - IP: 192.168.8.59 - Hostname: 90xxxxxe446e
	/con_nginx_sl - IP: 192.168.8.60 - Hostname: 91e9xxxxxc17
	/con_blogbug - IP: 192.168.8.82 - Hostname: 08b69xxxxx88
	/con_biblio_8_128 - IP: 192.168.8.88 - Hostname: xxxxxb55b90f
	/con_stray_126 - IP: 192.168.8.83 - Hostname: b0dxxxxx39fa
	/con_mysqldb - IP: 192.168.8.81 - Hostname: 0cf70xxxxxbb

2. From our network diagram above, we know: 

	a. con_nginx_sl is the reverse proxy
	→ *Append **top1** *

	b. con_proto83 and con_bulletin83 are children of the proxy con_nginx_s1
	→ *We reference these with 'con_nginx_sl'*

	c. con_bulletin83 is a stateless
	→ *No database dependency*

	d. con_proto83 requires database access
	→ *Include in the database rule*

	e. con_mysqldb is the database container
	→ *Declared using data+con_proto83*

	f. con_blogbug, con_biblio_8_128, and con_stray_126:
	- *Use the same database*
	- *Operate independently*
	- *Use a different tunnel / proxy*
	- *Declared using side+con_mysqldb*
	
8. Final mynetwork.txt, ready to upload to DockerJelly:
	```bash
	/con_proto83 - IP: 192.168.8.58 - Hostname: cff4xxxxxa84 - con_nginx_sl
	/con_bulletin83 - IP: 192.168.8.59 - Hostname: 90xxxxxe446e - con_nginx_sl
	/con_nginx_sl - IP: 192.168.8.60 - Hostname: 91e9xxxxxc17 - top1
	/con_blogbug - IP: 192.168.8.82 - Hostname: 08b69xxxxx88 - side+con_mysqldb
	/con_biblio_8_128 - IP: 192.168.8.88 - Hostname: xxxxxb55b90f - side+con_mysqldb
	/con_stray_126 - IP: 192.168.8.83 - Hostname: b0dxxxxx39fa - side+con_mysqldb
	/con_mysqldb - IP: 192.168.8.81 - Hostname: 0cf70xxxxxbb - data+con_proto83
	
### 📝 Notes on naming

- The con_ prefix is recommended, not required
- DockerJelly works fine without it
- The prefix simply improves readability and consistency

### 🚀 Requirements and how to install:

- PHP 7.4+ (PHP 8.x supported) 
- Web server (Apache / Nginx / PHP built-in server)
- Docker host (optional, for show_ip.sh)

### Install

- Copy index.php into your web root (e.g. www/, htdocs/, or /var/www/html)

- Or place it in a folder:
	```bash
	www/dockerjelly/
Then open:
```console
http://localhost/dockerjelly/
```
## ✔ Scope & limitations

### ✔ This tool assumes:

- One primary ingress (top1)
- DB dependencies are declared via data+container
- Containers without explicit DB linkage are detached

### ❌ This tool does NOT yet model:

- Multiple databases
- Cross-container non-DB dependencies
- Message queues, caches, or internal service meshes
- Network-level segmentation (macvlan vs bridge is abstracted)

## 🙏 Credits
Mermaid.js — for the incredible diagram engine (MIT License)

https://mermaid.js.org/

https://github.com/mermaid-js/mermaid

## 📄 License

MIT License

Copyright © 2025 Ferdinand Tumulak