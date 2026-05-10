<script>
    function checkNodeStatus() {
        fetch('/admin/nodes/status')
            .then(res => res.json())
            .then(data => {
                for (const [id, info] of Object.entries(data)) {
                    const isOnline = info.status === 'Online';

                    // Elementos principais
                    const indicator = document.querySelector(`.node-indicator-${id}`);
                    const text = document.querySelector(`.node-text-${id}`);
                    const ping = document.querySelector(`.node-ping-${id}`);

                    // Stats
                    const ram = document.querySelector(`.node-ram-${id}`);
                    const cpu = document.querySelector(`.node-cpu-${id}`);
                    const os = document.querySelector(`.node-os-${id}`);
                    const uptime = document.querySelector(`.node-uptime-${id}`);

                    // Versionamento
                    const version = document.querySelector(`.node-version-${id}`);
                    const updateBadge = document.querySelector(`.node-update-badge-${id}`);
                    const latestWrapper = document.querySelector(`.node-latest-wrapper-${id}`);
                    const latestVersion = document.querySelector(`.node-latest-version-${id}`);

                    // STATUS ONLINE/OFFLINE
                    if (indicator && text) {
                        if (isOnline) {
                            indicator.className = `relative w-2.5 h-2.5 rounded-full bg-cyan-500 shadow-[0_0_8px_#22d3ee] node-indicator-${id}`;

                            if (ping) {
                                ping.className = `absolute w-full h-full rounded-full bg-cyan-500 opacity-20 animate-ping node-ping-${id}`;
                            }

                            text.className = `text-[12px] font-bold text-cyan-400 uppercase tracking-widest node-text-${id}`;
                            text.textContent = 'Online';
                        } else {
                            indicator.className = `relative w-2.5 h-2.5 rounded-full bg-red-500 shadow-[0_0_8px_#ef4444] node-indicator-${id}`;

                            if (ping) {
                                ping.className = `absolute w-full h-full rounded-full bg-red-500 opacity-0 node-ping-${id}`;
                            }

                            text.className = `text-[12px] font-bold text-red-400 uppercase tracking-widest node-text-${id}`;
                            text.textContent = 'Offline';
                        }
                    }

                    // Atualiza stats
                    if (ram) {
                        ram.textContent = info.ram || '0%';
                    }

                    if (cpu) {
                        cpu.textContent = info.cpu || '0%';
                    }

                    if (os) {
                        os.textContent = info.os || 'Linux';
                    }

                    if (uptime) {
                        uptime.textContent = info.uptime || '0h 0m';
                    }

                    // Atualiza versão
                    if (version) {
                        version.textContent = info.version || 'dev';
                    }

                    // Badge de atualização
                    if (updateBadge && latestWrapper && latestVersion) {
                        if (info.hasUpdate) {
                            updateBadge.classList.remove('hidden');
                            latestWrapper.classList.remove('hidden');

                            updateBadge.className =
                                `text-[10px] px-2 py-1 rounded-full font-bold uppercase tracking-wider ` +
                                `bg-amber-500/10 text-amber-400 border border-amber-500/20 ` +
                                `node-update-badge-${id}`;

                            latestVersion.textContent = info.latestVersion || '--';
                        } else {
                            updateBadge.classList.add('hidden');
                            latestWrapper.classList.add('hidden');
                        }
                    }
                }
            })
            .catch(err => console.error("Erro ao puxar o status:", err));
    }

    document.addEventListener('DOMContentLoaded', () => {
        checkNodeStatus();
        setInterval(checkNodeStatus, 5000);
    });
</script>