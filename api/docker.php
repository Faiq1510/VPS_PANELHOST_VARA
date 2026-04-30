<?php
function getNextPort($pdo, $type) {
    $columns = ['ssh' => 'ssh_port', 'web' => 'web_port', 'console' => 'console_port'];
    $starts = ['ssh' => 12000, 'web' => 21000, 'console' => 4000];
    $col = $columns[$type];
    $stmt = $pdo->query("SELECT MAX($col) as max_port FROM containers");
    $row = $stmt->fetch();
    return $row['max_port'] ? $row['max_port'] + 1 : $starts[$type];
}

function createContainer($pdo, $userId) {
    $sshPort = getNextPort($pdo, 'ssh');
    $webPort = getNextPort($pdo, 'web');
    $consolePort = getNextPort($pdo, 'console');
    $containerName = 'server' . $sshPort;
    $password = bin2hex(random_bytes(6));
    $domain = $sshPort . '.xlim.pentester.biz.id';

    $cmd = "docker run -d --name {$containerName} --restart unless-stopped "
     . "--sysctl net.ipv4.ip_unprivileged_port_start=0 "
     . "-p {$sshPort}:22 -p {$webPort}:80 -p {$consolePort}:4200 "
     . "-e ROOT_PASSWORD={$password} vps-hosting:latest 2>&1";
    
    exec($cmd, $output, $code);

    if ($code === 0) {
        $stmt = $pdo->prepare("INSERT INTO containers 
            (user_id, container_name, domain, ssh_port, web_port, console_port, root_password, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'running')");
        $stmt->execute([$userId, $containerName, $domain, $sshPort, $webPort, $consolePort, $password]);
        return ['success' => true, 'container' => $containerName, 'password' => $password, 'ssh_port' => $sshPort];
    }
    return ['success' => false, 'error' => implode("\n", $output)];
}

function stopContainer($name) {
    exec("docker stop {$name} 2>&1", $out, $code);
    return $code === 0;
}

function startContainer($name) {
    exec("docker start {$name} 2>&1", $out, $code);
    return $code === 0;
}

function deleteContainer($name) {
    exec("docker stop {$name} && docker rm {$name} 2>&1", $out, $code);
    return $code === 0;
}

function getContainerStats($name) {
    exec("docker stats {$name} --no-stream --format '{{.CPUPerc}}|{{.MemUsage}}' 2>&1", $out);
    if (!empty($out[0]) && strpos($out[0], '|') !== false) {
        [$cpu, $mem] = explode('|', $out[0]);
        return ['cpu' => trim($cpu), 'mem' => trim($mem)];
    }
    return ['cpu' => 'N/A', 'mem' => 'N/A'];
}
