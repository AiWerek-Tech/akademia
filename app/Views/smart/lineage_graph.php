<?= $this->extend('layouts/admin') ?>

<?= $this->section('additional_css') ?>
<style>
/* ========= Lineage Graph Styles ========= */
.lineage-graph-container { min-height: 500px; background: #f8f9fc; border-radius: 16px; position: relative; overflow: hidden; }
.lineage-node { cursor: pointer; transition: all .2s ease; }
.lineage-node:hover { filter: brightness(1.1); }

/* Mermaid-style flow for server-side rendering */
.lineage-flow { display: flex; flex-direction: column; gap: 32px; padding: 24px; }
.lineage-tier { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; align-items: flex-start; }
.lineage-tier-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .06em; font-weight: 700; color: #6c757d; writing-mode: vertical-lr; transform: rotate(180deg); padding: 8px 4px; }
.lineage-card {
    background: #fff; border-radius: 12px; padding: 10px 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,.08); border-left: 4px solid #e9ecef;
    max-width: 240px; font-size: .78rem; transition: all .25s ease;
    cursor: pointer; position: relative;
}
.lineage-card:hover { box-shadow: 0 6px 18px rgba(0,0,0,.15); transform: translateY(-2px); }
.lineage-card.type-regulation  { border-left-color: #6366f1; }
.lineage-card.type-cp          { border-left-color: #0ea5e9; }
.lineage-card.type-tp          { border-left-color: #22c55e; }
.lineage-card.type-atp         { border-left-color: #f59e0b; }
.lineage-card.type-rpp         { border-left-color: #ef4444; }
.lineage-card .card-code { font-size: .68rem; font-weight: 700; color: #6366f1; }
.lineage-card .card-label { font-size: .75rem; color: #334155; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

/* Interactive Highlighting States */
.lineage-card.is-selected {
    box-shadow: 0 0 0 3px #6366f1, 0 8px 24px rgba(99,102,241,.35) !important;
    transform: scale(1.05) !important;
    z-index: 10;
}
.lineage-card.is-active-path {
    box-shadow: 0 0 0 2px #0ea5e9, 0 4px 14px rgba(14,165,233,.3) !important;
    transform: translateY(-1px);
    z-index: 5;
    background: #f0f9ff;
}
.lineage-card.is-dimmed {
    opacity: 0.22 !important;
    filter: grayscale(80%) !important;
}

.lineage-connector { text-align: center; color: #cbd5e1; font-size: 1.2rem; transition: color .2s ease; }
.lineage-connector.is-active { color: #6366f1; }

/* Health score badge */
.health-score { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-size: .82rem; font-weight: 700; }

/* Gap alerts */
.gap-alert { border-radius: 12px; padding: 10px 14px; font-size: .78rem; border: none; }
.gap-alert.severity-warning { background: #fef3c7; color: #92400e; }
.gap-alert.severity-info    { background: #dbeafe; color: #1e40af; }
</style>
<?= $this->endSection() ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-gradient px-3 py-2 rounded-pill fw-semibold text-xs" style="background: linear-gradient(135deg, #6366f1, #06b6d4); color: #fff;">
                    <i data-lucide="network" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Lineage Graph
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Peta Lineage Kurikulum</h1>
            <p class="text-muted mb-0">Visualisasi alur penurunan: Regulasi → CP → TP → ATP → RPP. Deteksi celah cakupan otomatis.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('smart/lineage-graph') ?>" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()">
                        <option value="">Pilih Mapel untuk menampilkan lineage...</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (int) $selectedSubject === (int) $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?> <?= !empty($s['code']) ? '(' . esc($s['code']) . ')' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary shadow-sm px-4"><i data-lucide="search" class="w-3.5 h-3.5 me-1"></i> Analisis</button>
                    <a href="<?= base_url('smart/lineage-graph') ?>" class="btn btn-sm btn-outline-secondary shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$graphData): ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center text-muted py-5">
                <i data-lucide="network" class="w-12 h-12 text-muted mb-3 d-inline-block" style="width:48px;height:48px;"></i><br>
                <span class="fw-semibold">Pilih mata pelajaran</span><br>
                <small>untuk menampilkan peta lineage kurikulum interaktif.</small>
            </div>
        </div>
    <?php else: ?>
        <?php
        $stats  = $graphData['stats'];
        $health = $graphData['health'];
        $gaps   = $graphData['gaps'];
        $nodes  = $graphData['nodes'];
        $edges  = $graphData['edges'];
        ?>

        <!-- Stats + Health -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-2">
                <div class="stat-card-smart bg-white shadow-sm" style="border-radius:16px; padding:16px;">
                    <div class="stat-label" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;font-weight:600;">Regulasi</div>
                    <div class="h4 fw-bold mb-0 text-indigo"><?= $stats['regulations'] ?></div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-card-smart bg-white shadow-sm" style="border-radius:16px; padding:16px;">
                    <div class="stat-label" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;font-weight:600;">CP</div>
                    <div class="h4 fw-bold mb-0 text-info"><?= $stats['outcomes'] ?></div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-card-smart bg-white shadow-sm" style="border-radius:16px; padding:16px;">
                    <div class="stat-label" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;font-weight:600;">TP</div>
                    <div class="h4 fw-bold mb-0 text-success"><?= $stats['objectives'] ?></div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-card-smart bg-white shadow-sm" style="border-radius:16px; padding:16px;">
                    <div class="stat-label" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;font-weight:600;">RPP</div>
                    <div class="h4 fw-bold mb-0 text-danger"><?= $stats['plans'] ?></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card-smart bg-white shadow-sm d-flex align-items-center gap-3" style="border-radius:16px; padding:16px;">
                    <div>
                        <div class="stat-label" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;font-weight:600;">Skor Kesehatan</div>
                        <div class="h3 fw-bold mb-0 text-<?= $health['color'] ?>"><?= $health['score'] ?>/100</div>
                    </div>
                    <span class="health-score bg-<?= $health['color'] ?>-subtle text-<?= $health['color'] ?>"><?= esc($health['label']) ?></span>
                </div>
            </div>
        </div>

        <!-- Gap Alerts -->
        <?php if (!empty($gaps)): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 px-4">
                <h6 class="fw-bold text-gray-800 mb-0"><i data-lucide="alert-triangle" class="w-4 h-4 me-2 d-inline-block"></i>Celah Cakupan Terdeteksi (<?= count($gaps) ?>)</h6>
            </div>
            <div class="card-body d-flex flex-column gap-2">
                <?php foreach (array_slice($gaps, 0, 10) as $gap): ?>
                    <div class="gap-alert severity-<?= esc($gap['severity']) ?>">
                        <i data-lucide="<?= $gap['severity'] === 'warning' ? 'alert-triangle' : 'info' ?>" class="w-3.5 h-3.5 me-1 d-inline-block"></i>
                        <?= esc($gap['message']) ?>
                    </div>
                <?php endforeach; ?>
                <?php if (count($gaps) > 10): ?>
                    <div class="text-muted text-xs">...dan <?= count($gaps) - 10 ?> celah lainnya.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lineage Flow Visualisation -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h6 class="fw-bold text-gray-800 mb-0"><i data-lucide="git-branch" class="w-4 h-4 me-2 d-inline-block"></i>Alur Lineage Kurikulum Interaktif</h6>
                    <p class="text-muted text-xs mb-0">Klik pada salah satu item untuk menyorot alur relasi penuh (induk & turunan).</p>
                </div>
                <div id="highlightInfoBar" class="d-none d-flex align-items-center gap-2">
                    <span class="badge bg-primary rounded-pill px-3 py-1.5 text-xs d-flex align-items-center gap-1">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i> <span id="highlightNodeLabel">-</span>
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="resetHighlight()">
                        Reset Sorotan
                    </button>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="lineage-graph-container">
                    <div class="lineage-flow">
                        <?php
                        // Group nodes by type for tiered display
                        $tiers = [
                            'regulation' => ['label' => 'REGULASI',  'type_class' => 'type-regulation', 'nodes' => []],
                            'cp'         => ['label' => 'CP',        'type_class' => 'type-cp',         'nodes' => []],
                            'tp'         => ['label' => 'TP',        'type_class' => 'type-tp',         'nodes' => []],
                            'atp'        => ['label' => 'ATP',       'type_class' => 'type-atp',        'nodes' => []],
                            'rpp'        => ['label' => 'RPP',       'type_class' => 'type-rpp',        'nodes' => []],
                        ];
                        foreach ($nodes as $node) {
                            $group = $node['group'] ?? 'regulation';
                            if (isset($tiers[$group])) {
                                $tiers[$group]['nodes'][] = $node;
                            }
                        }
                        ?>

                        <?php foreach ($tiers as $tierKey => $tier): ?>
                            <?php if (empty($tier['nodes'])) continue; ?>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-light text-dark fw-bold px-3 py-1 rounded-pill text-xs"><?= $tier['label'] ?></span>
                                    <span class="text-muted text-xs"><?= count($tier['nodes']) ?> item</span>
                                </div>
                                <div class="lineage-tier">
                                    <?php foreach ($tier['nodes'] as $node): ?>
                                        <div class="lineage-card <?= $tier['type_class'] ?>"
                                             id="node-<?= esc($node['id']) ?>"
                                             data-node-id="<?= esc($node['id']) ?>"
                                             data-node-code="<?= esc($node['code'] ?? '') ?>"
                                             data-node-label="<?= esc($node['label']) ?>"
                                             onclick="highlightNodePath('<?= esc($node['id']) ?>')">
                                            <div class="card-code"><?= esc($node['code'] ?? '') ?></div>
                                            <div class="card-label"><?= esc($node['label']) ?></div>
                                            <?php if (!empty($node['status'])): ?>
                                                <span class="badge bg-light text-dark mt-1" style="font-size:.6rem;"><?= esc($node['status']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php if ($tierKey !== 'rpp'): ?>
                                <div class="lineage-connector"><i data-lucide="arrow-down" class="w-5 h-5 d-inline-block"></i></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <script>
        const lineageEdges = <?= json_encode($edges) ?>;
        const lineageNodes = <?= json_encode($nodes) ?>;
        let selectedNodeId = null;

        // Build adjacency lists
        const forwardAdj = {};  // from -> [to]
        const backwardAdj = {}; // to -> [from]

        lineageEdges.forEach(e => {
            if (!forwardAdj[e.from]) forwardAdj[e.from] = [];
            forwardAdj[e.from].push(e.to);

            if (!backwardAdj[e.to]) backwardAdj[e.to] = [];
            backwardAdj[e.to].push(e.from);
        });

        function getUpstream(nodeId, visited = new Set()) {
            visited.add(nodeId);
            const parents = backwardAdj[nodeId] || [];
            parents.forEach(p => {
                if (!visited.has(p)) getUpstream(p, visited);
            });
            return visited;
        }

        function getDownstream(nodeId, visited = new Set()) {
            visited.add(nodeId);
            const children = forwardAdj[nodeId] || [];
            children.forEach(c => {
                if (!visited.has(c)) getDownstream(c, visited);
            });
            return visited;
        }

        function highlightNodePath(nodeId) {
            if (selectedNodeId === nodeId) {
                resetHighlight();
                return;
            }

            selectedNodeId = nodeId;
            const upstream = getUpstream(nodeId);
            const downstream = getDownstream(nodeId);
            const allConnected = new Set([...upstream, ...downstream]);

            const allCards = document.querySelectorAll('.lineage-card');
            allCards.forEach(card => {
                const id = card.dataset.nodeId;
                card.classList.remove('is-selected', 'is-active-path', 'is-dimmed');

                if (id === nodeId) {
                    card.classList.add('is-selected');
                } else if (allConnected.has(id)) {
                    card.classList.add('is-active-path');
                } else {
                    card.classList.add('is-dimmed');
                }
            });

            // Update info bar
            const nodeEl = document.querySelector(`[data-node-id="${nodeId}"]`);
            const label = nodeEl ? (nodeEl.dataset.nodeCode || nodeEl.dataset.nodeLabel || nodeId) : nodeId;
            document.getElementById('highlightNodeLabel').textContent = `Menyorot: ${label} (${allConnected.size} item terhubung)`;
            document.getElementById('highlightInfoBar').classList.remove('d-none');
        }

        function resetHighlight() {
            selectedNodeId = null;
            document.querySelectorAll('.lineage-card').forEach(card => {
                card.classList.remove('is-selected', 'is-active-path', 'is-dimmed');
            });
            document.getElementById('highlightInfoBar').classList.add('d-none');
        }
        </script>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
