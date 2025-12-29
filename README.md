# DockerJelly ଳ

**DockerJelly** is a visual + ASCII Docker network notebook generator for humans.

It helps you map container relationships, document intent, and generate
Mermaid flowcharts — without parsing Docker internals or touching the Docker socket.

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
	 

...to be continued