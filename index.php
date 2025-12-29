<?php
$diagram = '';

// function isTunnel(array $node): bool
// {
    // return str_contains($node['link'], 'tunnel');
// }


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
    $link = $node['link'];

    if ($link === 'top1') return '☁︎';
    if (str_starts_with($link, 'data+')) return '🗄️';
    if (str_starts_with($link, 'side+')) return '⬡︎';
    if (str_contains($link, 'tunnel')) return '☁︎';

    return '🐘';
}

function buildTree(array $nodes): array {
    $tree = [
        'root' => null,
        'children' => [],
        'db' => null,
        'side' => []
    ];

    // find root
    foreach ($nodes as $n) {
        if ($n['link'] === 'top1') {
            $tree['root'] = $n;
            break;
        }
    }

    if (!$tree['root']) {
        return $tree;
    }

    $rootKey = keyOf($tree['root']['name']);

    // root children
    foreach ($nodes as $n) {
        if ($n['link'] === $rootKey) {
            $tree['children'][] = $n;
        }
    }

    // db
    foreach ($nodes as $n) {
        if (str_starts_with($n['link'], 'data+')) {
            $tree['db'] = $n;
            $dbKey = keyOf($n['name']);

            // side containers
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

    // root
    $root = $tree['root'];
    $out .= '[<a href="' . pageFor($root['name']) . '" target="_blank">'
         . htmlspecialchars($root['name']) . '</a>'
         . ' - ' . htmlspecialchars($root['ip'])
         . ' - ' . htmlspecialchars($root['host']) . "]\n";

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

    $root = $tree['root'];
    $rootId = $getId($root['name']);
    $lines[] = "{$rootId}[" . mermaidLabel($root) . "]";

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
<title>DockerJelly ASCII Network Diagram Gen. v1.0</title>
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

<h2>DockerJelly ASCII Network Diagram Generator v1.0 (Using ./show_ip.sh)</h2>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="file" accept=".txt" required>
    <br>
    <button type="submit">Generate</button>
</form>

<h3>Copy the ./show_ip.sh results, append for each container, save as network.txt</h3>
	<ul>
		<li>top1 → root container (nginx or apache)</li>
		<li>con_xxx → child of root container</li>
		<li>data+con_a+con_b → DB container serving those apps</li>
		<li>side+con_mysql → side/failsafe container attached to DB (optional standalone setup)</li>
	</ul>

<h3>Example of network.txt:</h3>
<p>
/con_quizbee - IP: 192.168.1.65 - Hostname: xxxxxxxx27e8 - con_mynginx<br>
/con_biblio - IP: 192.168.1.67 - Hostname: xxxxxxxx6c42 - con_mynginx<br>
/con_nginx - IP: 192.168.1.69 - Hostname: xxxxxxxx76b7 - top1<br>
/con_quizbee_back - IP: 192.168.1.64 - Hostname: xxxxxxxx1ff4 - side+con_mysql<br>
/con_biblio_back - IP: 192.168.1.77 - Hostname: xxxxxxxx3ca4 - side+con_mysql<br>
/con_mysql - IP: 192.168.1.79 - Hostname: xxxxxxxx51a3 - data+con_bibinow<br>
</p>

<?php 
	if (isset($nodes)) {
		echo "<h3>Result:</h3>";
		echo buildDiagramHtml($nodes);
	?>
		<button id="saveBtn">Save as HTML</button>
		<h3>*note: After you save the html, you can fill out the contents of each .txt file links labeled by container names. You can append changes in those .txt file for improvements on your docker network </h3>
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

		<h2>Docker simple ASCII Network Diagram Generator (Using ./show_ip.sh + Rules)</h2>
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