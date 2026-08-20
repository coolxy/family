<?php
// 文件: pages/members.php - 族人名录（字体统一加大）
$members = getAllMembers();
$loggedIn = isLoggedIn();

// 分页设置
$page = isset($_GET['p']) ? intval($_GET['p']) : 1;
$perPage = 50;
$total = count($members);
$totalPages = ceil($total / $perPage);
if ($page < 1) $page = 1;
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;
$pageMembers = array_slice($members, $offset, $perPage);
?>
<div class="page-header">
    <h2>👥 族人名录</h2>
    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:8px;">
        <?php if ($loggedIn): ?>
            <button class="btn" onclick="exportMembersCSV()">📊 导出 CSV</button>
            <button class="btn" onclick="exportMembersExcel()">📊 导出 Excel</button>
            <button class="btn" onclick="exportToWord('members')">📄 导出为 Word</button>
        <?php else: ?>
            <button class="btn" onclick="showLogin()" style="opacity:0.7;">🔒 登录后导出</button>
        <?php endif; ?>
    </div>
</div>
<div class="list-toolbar">
    <?php if ($loggedIn): ?>
        <button class="btn" onclick="openAddMember()">➕ 添丁入谱</button>
    <?php else: ?>
        <button class="btn" onclick="showLogin()" style="opacity:0.7;">🔒 登录后添丁</button>
    <?php endif; ?>
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="🔍 搜索成员姓名..." oninput="searchMembers()">
    </div>
</div>

<!-- 移除局部滚动容器，直接显示列表 -->
<div id="memberList" class="member-list">
    <?php if (empty($pageMembers)): ?>
        <div class="empty-state">暂未录入族人，请点击「添丁入谱」开始。</div>
    <?php else: ?>
        <?= renderMemberHierarchy($pageMembers, 0) ?>
    <?php endif; ?>
</div>

<!-- 分页导航 -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=members&p=<?= $page-1 ?>">‹ 上一页</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i == $page): ?>
            <span class="current"><?= $i ?></span>
        <?php else: ?>
            <a href="?page=members&p=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
        <a href="?page=members&p=<?= $page+1 ?>">下一页 ›</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<style>
/* ===== ★★★ 族人名录字体统一加大 ★★★ ===== */

/* 搜索框字体加大 */
.search-box input {
    font-size: 1.05rem;
    padding: 10px 18px;
}

/* 成员卡片整体字体加大 */
.member-card {
    padding: 14px 20px;
    margin-bottom: 10px;
}

.member-info {
    gap: 6px 16px;
}

/* 成员姓名 - 加大 */
.member-name {
    font-size: 1.2rem;
    font-weight: 700;
}

/* 排行标签 */
.member-rank {
    font-size: 0.85rem;
    padding: 2px 12px;
}

/* 详细信息 */
.member-detail {
    font-size: 0.95rem;
    color: #6f5b47;
}

/* 操作按钮 */
.member-actions .btn-sm {
    font-size: 0.85rem;
    padding: 6px 16px;
}

/* 子节点缩进 */
.member-children {
    padding-left: 34px;
    margin-left: 18px;
}

/* 分页按钮 */
.pagination a,
.pagination span {
    font-size: 1rem;
    padding: 8px 18px;
    min-width: 42px;
}

/* 空状态 */
.empty-state {
    font-size: 1.05rem;
    padding: 30px;
}

/* 分页导航字体 */
.pagination {
    gap: 8px;
    padding: 20px 0 10px;
}

/* 分页当前页 */
.pagination .current {
    font-weight: 600;
}

/* 列表工具栏按钮 */
.list-toolbar .btn {
    font-size: 1rem;
    padding: 10px 24px;
}

/* 页码显示 */
.pagination a,
.pagination span {
    font-size: 1rem;
}
</style>

<script>
// ============================================================
// 导出CSV（需登录）
// ============================================================
function exportMembersCSV() {
    <?php if (!$loggedIn): ?>
        showLogin();
        return;
    <?php endif; ?>
    
    var members = <?= json_encode($members) ?>;
    if (!members || members.length === 0) {
        alert('没有成员数据可导出');
        return;
    }
    
    var headers = ['序号', '姓名', '性别', '世次', '父母', '排名', '出生日期', '终年日期', '履历', 
                   '配偶姓名', '配偶出生日期', '配偶终年日期', '配偶履历', '镇(市)', '区(县或村)', '子女数量', '备注'];
    
    var rows = [];
    rows.push(headers.join(','));
    
    members.forEach(function(m, index) {
        var fatherName = getMemberNameById(m.father_id) || '';
        var motherName = m.mother_name || '';
        var parentName = fatherName || motherName;
        if (fatherName && motherName) {
            parentName = fatherName + '·' + motherName;
        }
        
        var row = [
            index + 1,
            m.name || '',
            m.gender || '',
            '第' + (m.generation || '') + '世',
            parentName,
            m.rank || '',
            m.birth_date || '',
            m.death_date || '',
            m.biography || '',
            m.spouse_name || '',
            m.spouse_birth || '',
            m.spouse_death || '',
            m.spouse_biography || '',
            '', // 镇(市)
            '', // 区(县或村)
            '', // 子女数量
            ''  // 备注
        ];
        rows.push(row.join(','));
    });
    
    var csvContent = '\uFEFF' + rows.join('\n');
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = '<?= getClanFamilyName() ?>'_族谱成员信息_' + new Date().toISOString().slice(0,10) + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
}

// ============================================================
// 导出Excel（需登录）
// ============================================================
function exportMembersExcel() {
    <?php if (!$loggedIn): ?>
        showLogin();
        return;
    <?php endif; ?>
    
    var members = <?= json_encode($members) ?>;
    if (!members || members.length === 0) {
        alert('没有成员数据可导出');
        return;
    }
    
    var html = `
    <html xmlns:o="urn:schemas-microsoft-com:office:office" 
          xmlns:x="urn:schemas-microsoft-com:office:excel" 
          xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta charset="UTF-8">
        <!--[if gte mso 9]>
        <xml>
            <x:ExcelWorkbook>
                <x:ExcelWorksheets>
                    <x:ExcelWorksheet>
                        <x:Name>世系名册</x:Name>
                        <x:WorksheetOptions>
                            <x:DisplayGridlines/>
                        </x:WorksheetOptions>
                    </x:ExcelWorksheet>
                </x:ExcelWorksheets>
            </x:ExcelWorkbook>
        </xml>
        <![endif]-->
        <style>
            table { border-collapse: collapse; font-family: "宋体", "SimSun"; font-size: 11pt; }
            th { background: #dac9b6; font-weight: bold; border: 1px solid #999; padding: 4px 8px; text-align: center; }
            td { border: 1px solid #999; padding: 4px 8px; }
            .title { font-size: 16pt; font-weight: bold; text-align: center; padding: 10px; }
            .subtitle { font-size: 10pt; color: #666; text-align: center; }
        </style>
    </head>
    <body>
        <table>
            <tr><td colspan="17" class="title"><?= getClanFamilyName() ?> · 族人名录</td></tr>
            <tr><td colspan="17" class="subtitle">导出时间：${new Date().toLocaleString()}</td></tr>
            <tr>
                <th>序号</th><th>姓名</th><th>性别</th><th>世次</th><th>父母</th><th>排名</th>
                <th>出生日期</th><th>终年日期</th><th>履历</th>
                <th>配偶姓名</th><th>配偶出生日期</th><th>配偶终年日期</th><th>配偶履历</th>
                <th>镇(市)</th><th>区(县或村)</th><th>子女数量</th><th>备注</th>
            </tr>
    `;
    
    members.forEach(function(m, index) {
        var fatherName = getMemberNameById(m.father_id) || '';
        var motherName = m.mother_name || '';
        var parentName = fatherName || motherName;
        if (fatherName && motherName) {
            parentName = fatherName + '·' + motherName;
        }
        
        html += `
            <tr>
                <td>${index + 1}</td>
                <td>${escapeHtml(m.name || '')}</td>
                <td>${m.gender || ''}</td>
                <td>第${m.generation || ''}世</td>
                <td>${escapeHtml(parentName)}</td>
                <td>${m.rank || ''}</td>
                <td>${m.birth_date || ''}</td>
                <td>${m.death_date || ''}</td>
                <td>${escapeHtml(m.biography || '')}</td>
                <td>${m.spouse_name || ''}</td>
                <td>${m.spouse_birth || ''}</td>
                <td>${m.spouse_death || ''}</td>
                <td>${escapeHtml(m.spouse_biography || '')}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        `;
    });
    
    html += `</table></body></html>`;
    
    var blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = '族谱成员信息_' + new Date().toISOString().slice(0,10) + '.xls';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
}

function getMemberNameById(id) {
    var members = <?= json_encode($members) ?>;
    if (!id) return '';
    var found = members.find(function(m) { return m.id == id; });
    return found ? found.name : '';
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
</script>