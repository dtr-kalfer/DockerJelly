<?php
$diagram = '';

function hasTunnel(array $nodes): bool {
		foreach ($nodes as $n) {
				$link = trim($n['link']);

				foreach ($nodes as $n) {
						if ($n['link'] === $rootKey) {
								$tree['children'][] = $n;
						}
				}

		}
}

function findByLink(array $nodes, string $link): ?array {
    foreach ($nodes as $n) {
        if ($n['link'] === $link) return $n;
    }
    return null;
}


function labelOf(string $name): string {
    return ltrim($name, '/');
}

function mermaidLabel(array $node): string
{
    $name = ltrim($node['name'], '/');
    $ip   = htmlspecialchars($node['ip'], ENT_QUOTES);
    $host = htmlspecialchars($node['host'], ENT_QUOTES);

    return mermaidIcon($node) . " {$name}<br>{$ip}<br>{$host}";
}

function pageFor(string $containerName): string {
    return ltrim($containerName, '/') . '.txt';
}


function keyOf(string $name): string {
    return ltrim($name, '/');
}

function mermaidIcon(array $node): string
{
    return match (true) {
        $node['link'] === 'tunn' => '☁︎',
        $node['link'] === 'prxy' => '🧭',
        $node['link'] === 'top1' => '☁︎',
        str_starts_with($node['link'], 'data+') => '🗄️',
        str_starts_with($node['link'], 'side+') => '⬡︎',
        default => '🐘',
    };
}


function buildTree(array $nodes): array {
    $tree = [
        'tunnel'   => null,
        'proxy'    => null,
        'root'     => null,
        'children' => [],
        'db'       => null,
        'side'     => []
    ];

    // --- NEW: tunnel / proxy detection ---
    $tree['tunnel'] = findByLink($nodes, 'tunn');
    $tree['proxy']  = findByLink($nodes, 'prxy');

    // Determine root logic
    if ($tree['tunnel'] && $tree['proxy']) {
        $tree['root'] = $tree['proxy'];
    } else {
        // fallback to legacy top1
        $tree['root'] = findByLink($nodes, 'top1');
    }

    if (!$tree['root']) {
        return $tree;
    }

    $rootKey = keyOf($tree['root']['name']);

    // children of root (proxy or top1)
		foreach ($nodes as $n) {
				$link = trim($n['link']);

				if (
						$link === $rootKey || 
						$link === "app+{$rootKey}"
				) {
						$tree['children'][] = $n;
				}
		}

    // DB + side logic (unchanged)
    foreach ($nodes as $n) {
        if (str_starts_with($n['link'], 'data+')) {
            $tree['db'] = $n;
            $dbKey = keyOf($n['name']);

            foreach ($nodes as $side) {
                if (str_starts_with(trim($side['link']), "side+{$dbKey}")) {
                    $tree['side'][] = $side;
                }
            }
        }
    }

    return $tree;
}


function renderNodeHtml(array $node, string $prefix, bool $isLast, string $suffix = ''): string {
    $branch = $isLast ? '└─ ' : '├─ ';

    $name = htmlspecialchars($node['name']);
    $ip   = htmlspecialchars($node['ip']);
    $host = htmlspecialchars($node['host']);

    $url  = pageFor($node['name']);
		
		return $prefix . $branch .
					 '[<a href="' . $url . '" target="_blank">' . $name . '</a>'
					 . ' - ' . $ip
					 . ' - ' . $host . "]"
					 . ($suffix ? " {$suffix}" : '')
					 . "\n";
}



function parseFile(array $lines): array {
    $nodes = [];

    foreach ($lines as $line) {
        if (!trim($line)) continue;

				preg_match(
						'#^(/[\w_]+)\s+-\s+IP:\s+(.+?)\s+-\s+Hostname:\s+([\w\d]+)\s+-\s+(.+)$#',
						$line,
						$m
				);


        if (!$m) continue;

        $nodes[$m[1]] = [
            'name' => $m[1],
            'ip' => $m[2],
            'host' => $m[3],
            'link' => trim($m[4])
        ];
    }

    return $nodes;
}

function dbConsumers(array $db): array {
    if (!str_starts_with($db['link'], 'data+')) {
        return [];
    }

    $parts = explode('+', $db['link']);
    array_shift($parts); // remove "data"

    return $parts; // ['con_dtr74', 'con_xxx']
}


function buildDiagramHtml(array $nodes): string {
    $tree = buildTree($nodes);
		
		$dbConsumers = [];
		if ($tree['db']) {
				$dbConsumers = dbConsumers($tree['db']);
		}
		
    if (!$tree['root']) {
        return "<pre>No top1 container found.</pre>";
    }

    $out = "<pre>";

		// OPTIONAL virtual parent (tunnel)
		if ($tree['tunnel']) {
				$t = $tree['tunnel'];

				$out .= '[<a href="' . pageFor($t['name']) . '" target="_blank">'
						 . htmlspecialchars($t['name']) . '</a>'
						 . ' - (' . htmlspecialchars($t['ip']) . ')'
						 . ' - ' . htmlspecialchars($t['host'])
						 . "]\n";
		}

		// root (proxy)
		$root = $tree['root'];
		$out .= '[<a href="' . pageFor($root['name']) . '" target="_blank">'
				 . htmlspecialchars($root['name']) . '</a>'
				 . ' - ' . htmlspecialchars($root['ip'])
				 . ' - ' . htmlspecialchars($root['host'])
				 . "]\n";

    // children
    $childCount = count($tree['children']);
		foreach ($tree['children'] as $i => $child) {
				$childKey = keyOf($child['name']);

				$suffix = '';
				if ($tree['db'] && !in_array($childKey, $dbConsumers)) {
						$suffix = 'D';
				}

				$out .= renderNodeHtml(
						$child,
						'',
						($i === $childCount - 1 && !$tree['db']),
						$suffix
				);
		}

    // db + sides
    if ($tree['db']) {
        $out .= renderNodeHtml($tree['db'], '', empty($tree['side']));

        $sideCount = count($tree['side']);
        foreach ($tree['side'] as $i => $side) {
            $out .= renderNodeHtml(
                $side,
                '    ',
                $i === $sideCount - 1
            );
        }
    }

    $out .= "</pre>";

    return $out;
}

function buildDiagramMermaid(array $nodes): string
{
    $tree = buildTree($nodes);

    if (!$tree['root']) {
        return "flowchart LR\n%% No top1 container found";
    }

    $dbConsumers = [];
    if ($tree['db']) {
        $dbConsumers = dbConsumers($tree['db']);
    }

    $idMap = [];
    $nextId = 'A';

    $getId = function (string $name) use (&$idMap, &$nextId) {
        if (!isset($idMap[$name])) {
            $idMap[$name] = $nextId++;
        }
        return $idMap[$name];
    };

    $lines = [];
    $lines[] = "flowchart LR";

		// --- NEW: tunnel → proxy ---
		if ($tree['tunnel'] && $tree['proxy']) {
				$tunnelId = $getId($tree['tunnel']['name']);
				$proxyId  = $getId($tree['proxy']['name']);

				$lines[] = "{$tunnelId}[" . mermaidLabel($tree['tunnel']) . "]";
				$lines[] = "{$tunnelId} -.-> {$proxyId}[" . mermaidLabel($tree['proxy']) . "]";
		}

    $root = $tree['root'];
    $rootId = $getId($root['name']);
		
		if (!$tree['proxy']) {
				$lines[] = "{$rootId}[" . mermaidLabel($root) . "]";
		}		
    # $lines[] = "{$rootId}[" . mermaidLabel($root) . "]";

    // root children
		$dbKey = $tree['db'] ? keyOf($tree['db']['name']) : null;

		foreach ($tree['children'] as $child) {
				$childKey = keyOf($child['name']);

				// 🔥 ABSOLUTE RULE: root never links to DB
				if ($childKey === $dbKey) {
						continue;
				}

				$childId = $getId($child['name']);

				$edge = '<--->';
				$lines[] = "{$rootId} {$edge} {$childId}[" . mermaidLabel($child) . "]";

		}

    // db
		if ($tree['db']) {
				$dbId = $getId($tree['db']['name']);

				// consumers
				foreach (dbConsumers($tree['db']) as $consumerKey) {
						if (!isset($nodes['/' . $consumerKey])) continue;

						$consumerId = $getId('/' . $consumerKey);
						$lines[] = "{$consumerId} <---> {$dbId}[" . mermaidLabel($tree['db']) . "]";
				}

				// side containers
				foreach ($tree['side'] as $side) {
						$sideId = $getId($side['name']);
						$lines[] = "{$dbId} <---> {$sideId}[" . mermaidLabel($side) . "]";
				}
		}





    return implode("\n", array_unique($lines));
}

$mermaid = '';

if (!empty($_FILES['file']['tmp_name'])) {
    $lines = file($_FILES['file']['tmp_name']);
    $nodes = parseFile($lines);
    $diagram = buildDiagramHtml($nodes);
		$mermaid = buildDiagramMermaid($nodes);
} 
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>DockerJelly ASCII Network Diagram Gen. v2.0</title>
<style>
body {
    font-family: monospace;
    background: #111;
    color: #e0e0e0;
    padding: 20px;
}
pre {
    color: #e0e0e0;
    padding: 15px;
    border: 1px solid #444;
    overflow-x: auto;
		font-size: 16px;
}
input[type=file] {
    margin-bottom: 10px;
}
a, a:visited {
  color: #ccc; /* This makes visited links purple (the default color) */
}

</style>
</head>
<body>

<h2>DockerJelly ASCII Network Diagram Generator v2.0 (Using ./show_ip.sh)</h2>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="file" accept=".txt" required>
    <br>
    <button type="submit">Generate</button>
</form>

<h3>Copy the ./show_ip.sh results, append for each container, save as network.txt</h3>
	<ul>
		<li>tunn → Your tunnel container</li>
		<li>prxy → Proxy container, Connected to your tunnel (tunn)</li>
		<li>top1 → Use this as root container only if you don't have tunnel (Proxy only setup)</li>
		<li>app+con_xxx → child of prxy (if using tunnel), or top1</li>
		<li>data+con_a+con_b → DB container serving those apps</li>
		<li>side+con_mysql → side/failsafe container attached to DB</li>
	</ul>

<h3>Example of network.txt:</h3>
<p>
/con_tunnelx - IP: 192.168.88.99 - Hostname: xxxxxxxx3e42 - tunn<br>
/con_proxy1 - IP: 192.168.88.61 - Hostname: xxxxxxxx72ff - prxy<br>
/con_tracer_126 - IP: 192.168.88.83 - Hostname: xxxxxxxxd813 - side+con_mysql<br>
/con_proto83 - IP: 192.168.88.58 - Hostname: xxxxxxxx2a84 - app+con_proxy1<br>
/con_bulletin - IP: 192.168.88.59 - Hostname: xxxxxxxx446e - app+con_proxy1<br>
/con_blogbug - IP: 192.168.88.82 - Hostname: xxxxxxxx0188 - side+con_mysql<br>
/con_biblio_8_128 - IP: 192.168.88.88 - Hostname: xxxxxxxxb90f - side+con_mysql<br>
/con_mysql - IP: 192.168.88.81 - Hostname: xxxxxxxx2bbb - data+con_proto83<br>
</p>

<?php 
	if (isset($nodes)) {
		echo "<h3>Result:</h3>";
		echo buildDiagramHtml($nodes);
	?>
		<button id="saveBtn">Save as HTML</button>
		<h3>*note: After you save the html, place those .txt files alongside with the html and document your network</h3>
	<?php 
	} 
?>
<?php if ($mermaid): ?>
<h3>Mermaid Flowchart</h3>
<textarea rows="15" cols="90" readonly><?= htmlspecialchars($mermaid) ?></textarea>
<p>Copy and paste this into https://mermaid.live</p>
<h3>Mermaid Flowchart (Live)</h3>
	<div class="mermaid">
	<?= htmlspecialchars($mermaid) ?>
	</div>
<?php endif; ?>
<h4>Copyright (c) 2025 Ferdinand Tumulak - MIT License</h4>
	<script>
		document.getElementById('saveBtn').addEventListener('click', () => {
				// Grab the diagram <pre> content
				const preContent = document.querySelector('pre').outerHTML;

				// Build full HTML template
				const htmlContent = `
		<html>
		<head>
		<meta charset="utf-8">
		<title>Docker simple ASCII Network Diagram Gen.</title>
		<style>
		body {
				font-family: monospace;
				background: #eee;
				color: #111;
				padding: 20px;
		}
		pre {
				background: #eee;
				padding: 15px;
				border: 1px solid #444;
				overflow-x: auto;
				font-size: 18px;
		}
		input[type=file] {
				margin-bottom: 10px;
		}
		</style>
		</head>
		<body>

		<h2>Docker simple ASCII Network Diagram Generator (Using ./show_ip.sh + Rules) v2.0</h2>
		<h2>Please follow-up the contents of each .txt in the URL</h2>
		${preContent}

		</body>
		</html>
		`;

				// Create a blob and temporary link
				const blob = new Blob([htmlContent], {type: 'text/html'});
				const a = document.createElement('a');
				a.href = URL.createObjectURL(blob);
				a.download = 'docker_network_diagram.html';
				a.click();

				// Clean up
				URL.revokeObjectURL(a.href);
		});
	</script>
	<script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
	<script>
		mermaid.initialize({
			startOnLoad: true,
			theme: 'dark',
			flowchart: {
				curve: 'basis'
			}
		});
	</script>

	
</body>
</html>