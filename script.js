// 文件: script.js - 许氏家族族谱系统完整JavaScript（修复版）
// ============================================================

// ============================================================
// 全局变量（只声明一次）
// ============================================================
let branchTabCounter = 0;
let currentZoom = 1.0;
const minZoom = 0.3;
const maxZoom = 2.5;
const zoomStep = 0.1;

// ============================================================
// 搜索功能
// ============================================================
function searchMembers() {
    const input = document.getElementById('searchInput');
    if (!input) return;
    const filter = input.value.toLowerCase();
    const list = document.getElementById('memberList');
    if (!list) return;
    const items = list.querySelectorAll('.member-card');
    items.forEach(item => {
        const name = item.querySelector('.member-name')?.textContent?.toLowerCase() || '';
        item.style.display = name.includes(filter) ? '' : 'none';
    });
}

// ============================================================
// 世代自动计算
// ============================================================
function updateGeneration() {
    const fatherSelect = document.getElementById('fatherSelect');
    const genInput = document.getElementById('genInput');
    if (!fatherSelect || !genInput) return;
    const fatherId = fatherSelect.value;
    if (fatherId && typeof allMembers !== 'undefined') {
        let maxGen = 0;
        allMembers.forEach(m => {
            if (m.id == fatherId && m.generation > maxGen) maxGen = m.generation;
        });
        genInput.value = maxGen + 1;
    }
}

// ============================================================
// 标签页管理
// ============================================================
function switchTab(tabBtn) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    tabBtn.classList.add('active');
    document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
    const target = tabBtn.dataset.tab;
    const panel = document.getElementById('tab-' + target);
    if (panel) panel.classList.add('active');
}

function openBranchTab(memberId, memberName) {
    const existingTab = document.querySelector(`.tab-btn[data-branch="${memberId}"]`);
    if (existingTab) { switchTab(existingTab); return; }
    
    branchTabCounter++;
    const tabId = 'branch-' + branchTabCounter;
    const tabBar = document.getElementById('tabBar');
    if (!tabBar) return;
    
    const newTab = document.createElement('button');
    newTab.className = 'tab-btn branch-tab';
    newTab.dataset.tab = tabId;
    newTab.dataset.branch = memberId;
    newTab.innerHTML = `🌿 ${memberName} 支系 <span class="tab-close" onclick="event.stopPropagation();closeTab(this, '${tabId}')">×</span>`;
    tabBar.appendChild(newTab);
    
    const contentArea = document.getElementById('tabContent');
    if (!contentArea) return;
    const panel = document.createElement('div');
    panel.id = 'tab-' + tabId;
    panel.className = 'tab-panel';
    panel.innerHTML = '<div class="loading">加载支系图...</div>';
    contentArea.appendChild(panel);
    
fetch('member_actions.php?action=branch&id=' + memberId)
    .then(res => {
        if (!res.ok) throw new Error('网络请求失败');
        return res.json();
    })
    .then(data => {
        // 移除html判断分支，不再渲染静态树
        if (data && data.branchData) {
            // 清空加载文字，创建D3专用容器
            panel.innerHTML = '<div class="branch-panel-container" id="branchPanel_' + tabId + '" style="width:100%;height:100%;min-height:600px;position:relative;">' +
                '<div id="branchContainer_' + tabId + '" style="width:100%;height:100%;"></div>' +
                '</div>';
            const containerId = 'branchContainer_' + tabId;
            const container = document.getElementById(containerId);
            container._d3Data = data.branchData;
            // 多次延时渲染，等待DOM容器尺寸完成加载
            setTimeout(()=>renderD3Tree(containerId, data.branchData, true), 100);
            setTimeout(()=>renderD3Tree(containerId, data.branchData, true), 400);
            setTimeout(()=>renderD3Tree(containerId, data.branchData, true), 800);
        } else {
            panel.innerHTML = '<div class="empty-state">加载支系图失败：无数据</div>';
        }
    })
    .catch(() => { panel.innerHTML = '<div class="empty-state">加载失败，请重试</div>'; });
    
    switchTab(newTab);
}

function closeTab(closeBtn, tabId) {
    const tabBtn = closeBtn.closest('.tab-btn');
    const panel = document.getElementById('tab-' + tabId);
    if (tabBtn) tabBtn.remove();
    if (panel) panel.remove();
    const firstTab = document.querySelector('.tab-btn');
    if (firstTab) switchTab(firstTab);
}

document.addEventListener('click', function(e) {
    const tabBtn = e.target.closest('.tab-btn');
    if (tabBtn && !e.target.closest('.tab-close')) switchTab(tabBtn);
});

// ============================================================
// 模态框控制 - 修复：确保所有模态框函数正常工作
// ============================================================
function showLogin() { 
    const el = document.getElementById('loginModal');
    if (el) el.style.display = 'flex'; 
}
function closeLogin() { 
    const el = document.getElementById('loginModal');
    if (el) el.style.display = 'none'; 
}
function showRegister() { 
    const el = document.getElementById('registerModal');
    if (el) el.style.display = 'flex'; 
}
function closeRegister() { 
    const el = document.getElementById('registerModal');
    if (el) el.style.display = 'none'; 
}
function showProfile() { 
    const el = document.getElementById('profileModal');
    if (el) el.style.display = 'flex'; 
}
function closeProfile() { 
    const el = document.getElementById('profileModal');
    if (el) el.style.display = 'none'; 
}

// ★★★ 修复：添丁入谱弹出功能 ★★★
function openAddMember() {
    console.log('openAddMember 被调用, loggedIn:', typeof loggedIn !== 'undefined' ? loggedIn : 'undefined');
    
    // 检查登录状态
    if (typeof loggedIn !== 'undefined' && !loggedIn) { 
        if (typeof showLogin === 'function') {
            showLogin();
        } else {
            alert('请先登录');
        }
        return; 
    }
    
    var modal = document.getElementById('addMemberModal');
    if (!modal) {
        console.error('addMemberModal 元素不存在');
        alert('页面加载未完成，请刷新后重试');
        return;
    }
    
    // 强制显示
    modal.style.display = 'flex';
    console.log('addMemberModal 已显示');
}

function closeAddMember() { 
    const el = document.getElementById('addMemberModal');
    if (el) el.style.display = 'none'; 
}
function closeDetail() { 
    const el = document.getElementById('detailModal');
    if (el) el.style.display = 'none'; 
}

// ============================================================
// 成员详情 - 修复：确保详情弹窗正常工作
// ============================================================
function openDetail(memberId) {
    console.log('openDetail 被调用，ID:', memberId);
    
    // 确保 memberId 是有效数字
    if (!memberId || isNaN(memberId)) {
        console.error('无效的成员ID:', memberId);
        alert('无效的成员ID');
        return;
    }
    
    // 确保 modalBody 元素存在
    var modalBody = document.getElementById('modalBody');
    var detailModal = document.getElementById('detailModal');
    
    if (!modalBody) {
        console.error('modalBody 元素不存在');
        alert('页面加载未完成，请刷新后重试');
        return;
    }
    
    if (!detailModal) {
        console.error('detailModal 元素不存在');
        alert('页面加载未完成，请刷新后重试');
        return;
    }
    
    // 显示加载状态
    modalBody.innerHTML = '<div class="loading">加载中...</div>';
    detailModal.style.display = 'flex';
    
    fetch('member_actions.php?action=detail&id=' + memberId)
        .then(function(res) {
            if (!res.ok) throw new Error('网络请求失败: ' + res.status);
            return res.json();
        })
        .then(function(data) {
            console.log('返回数据:', data);
            
            if (data && data.error) {
                modalBody.innerHTML = '<p style="color:#b17a6b;">❌ ' + data.error + '</p>';
                return;
            }
            
            if (!data || !data.member) {
                modalBody.innerHTML = '<p style="color:#7e6855;">未找到该成员</p>';
                return;
            }
            
            // 调用渲染函数
            renderDetail(
                data.member, 
                data.parentSelect || '', 
                data.spouseLabel || '母亲姓名', 
                data.spouseValue || '', 
                data.isMotherNode || false
            );
            
            // 确保模态框可见
            detailModal.style.display = 'flex';
        })
        .catch(function(err) {
            console.error('加载成员详情出错:', err);
            modalBody.innerHTML = '<p style="color:#b17a6b;">❌ 加载失败: ' + err.message + '</p>';
        });
}

// 确保 openDetail 暴露到全局
window.openDetail = openDetail;

function renderDetail(m, parentSelectHtml, spouseLabel, spouseValue, isMotherNode) {
    var modalBody = document.getElementById('modalBody');
    if (!modalBody) return;
    var isLoggedIn = typeof loggedIn !== 'undefined' ? loggedIn : false;
    
    // 获取父亲和母亲的姓名
    var fatherName = '未记录';
    var motherName = '未记录';
    
    if (m.father_id) {
        fatherName = getMemberNameSync(m.father_id);
    }
    if (m.mother_id) {
        motherName = getMemberNameSync(m.mother_id);
    } else if (m.mother_name) {
        motherName = m.mother_name;
    }
    
    let spouseSection = '';
    if (m.spouse_name) {
        spouseSection = `
        <div class="detail-row"><span class="detail-label">配偶出生</span><span class="detail-value">${m.spouse_birth || '未记录'}</span></div>
        <div class="detail-row"><span class="detail-label">配偶逝世</span><span class="detail-value">${m.spouse_death || '健在'}</span></div>
        <div class="detail-row"><span class="detail-label">配偶娘家</span><span class="detail-value">${m.spouse_hometown || '未记录'}</span></div>
        <div class="detail-row" style="flex-direction:column; align-items:stretch; border-bottom:none;">
            <span class="detail-label">配偶履历</span>
            <div class="bio-text">${m.spouse_biography || '暂无记录'}</div>
        </div>
        `;
    }
    
    // 头像以长方形显示
    let avatarHtml = '';
    if (m.avatar) {
        avatarHtml = `<div class="detail-avatar-rect">
            <img src="${m.avatar}" alt="头像">
        </div>`;
    }
    
    // 编辑表单
    var editForm = '';
    if (isLoggedIn) {
        var parentSelectHtmlFinal = parentSelectHtml + `
        <div class="form-group"><label>${spouseLabel}</label>
            <input type="text" name="parent_spouse_name" value="${escapeHtml(spouseValue || '')}" placeholder="请输入${spouseLabel}">
        </div>
        `;
        
        editForm = `
        <div class="edit-form">
            <h4 style="margin-bottom:12px; color:#5f4a34;">✎ 编辑信息</h4>
            <form method="post" action="member_actions.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="${m.id}">
                <input type="hidden" name="is_mother_node" value="${isMotherNode ? '1' : '0'}">
                <input type="hidden" name="return_page" value="members">
                
                <div class="form-row">
                    <div class="form-group"><label>姓名</label><input type="text" name="name" value="${escapeHtml(m.name)}" required></div>
                    <div class="form-group"><label>性别</label>
                        <select name="gender">
                            <option value="男" ${m.gender==='男'?'selected':''}>男</option>
                            <option value="女" ${m.gender==='女'?'selected':''}>女</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group"><label>排行</label>
                        <select name="rank">
                            <option value="">-- 请选择 --</option>
                            <option value="长子" ${m.rank==='长子'?'selected':''}>长子</option>
                            <option value="次子" ${m.rank==='次子'?'selected':''}>次子</option>
                            <option value="三子" ${m.rank==='三子'?'selected':''}>三子</option>
                            <option value="四子" ${m.rank==='四子'?'selected':''}>四子</option>
                            <option value="五子" ${m.rank==='五子'?'selected':''}>五子</option>
                            <option value="长女" ${m.rank==='长女'?'selected':''}>长女</option>
                            <option value="次女" ${m.rank==='次女'?'selected':''}>次女</option>
                            <option value="三女" ${m.rank==='三女'?'selected':''}>三女</option>
                            <option value="四女" ${m.rank==='四女'?'selected':''}>四女</option>
                            <option value="五女" ${m.rank==='五女'?'selected':''}>五女</option>
                        </select>
                    </div>
                    <div class="form-group"><label>世代</label><input type="number" name="generation" value="${m.generation||1}"></div>
                </div>
                
                <div class="form-row">
                    <div class="form-group"><label>出生日期</label><input type="text" name="birth_date" value="${escapeHtml(m.birth_date||'')}"></div>
                    <div class="form-group"><label>逝世日期</label><input type="text" name="death_date" value="${escapeHtml(m.death_date||'')}"></div>
                </div>
                
                ${parentSelectHtmlFinal}
                
                <div class="form-row">
                    <div class="form-group"><label>职业</label><input type="text" name="profession" value="${escapeHtml(m.profession||'')}"></div>
                    <div class="form-group"><label>居住地址</label><input type="text" name="address" value="${escapeHtml(m.address||'')}"></div>
                </div>
                
                <div class="form-group"><label>生平履历</label><textarea name="biography" rows="3">${escapeHtml(m.biography||'')}</textarea></div>
                
                <div class="form-group">
                    <label>头像</label>
                    <div class="avatar-upload">
                        ${m.avatar ? `<img src="${m.avatar}" class="avatar-preview" id="avatarPreview">` : ''}
                        <input type="file" name="avatar" accept="image/*" onchange="previewAvatar(this)">
                    </div>
                </div>
                
                <div style="border-top:2px dashed #dacfc2; padding-top:14px; margin-top:8px;">
                    <p style="color:#7b5d42; font-weight:500; margin-bottom:10px;">💑 配偶信息</p>
                    
                    <div class="form-row">
                        <div class="form-group"><label>配偶姓名</label><input type="text" name="spouse_name" value="${escapeHtml(m.spouse_name||'')}"></div>
                        <div class="form-group"><label>配偶出生</label><input type="text" name="spouse_birth" value="${escapeHtml(m.spouse_birth||'')}"></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group"><label>配偶逝世</label><input type="text" name="spouse_death" value="${escapeHtml(m.spouse_death||'')}"></div>
                        <div class="form-group"><label>配偶娘家</label><input type="text" name="spouse_hometown" value="${escapeHtml(m.spouse_hometown||'')}"></div>
                    </div>
                    
                    <div class="form-group"><label>配偶履历</label><textarea name="spouse_biography" rows="2">${escapeHtml(m.spouse_biography||'')}</textarea></div>
                </div>
                
                <button type="submit" class="btn" style="width:100%; margin-top:12px;">💾 保存修改</button>
            </form>
        </div>
        `;
    } else {
        editForm = `
        <div style="margin-top:16px; padding-top:12px; border-top:1px solid #ede5db; text-align:center; color:#8a7a6a; font-size:0.9rem;">
            🔒 <a href="#" onclick="showLogin();return false;">登录</a> 后可编辑成员信息
        </div>
        `;
    }
    
    modalBody.innerHTML = `
        <div class="detail-header">
            <h3>📖 ${m.name}</h3>
        </div>
        <div class="detail-info-avatar-row">
            <div class="detail-info-list">
                <div class="detail-row"><span class="detail-label">ID</span><span class="detail-value">#${m.id}</span></div>
                <div class="detail-row"><span class="detail-label">性别</span><span class="detail-value">${m.gender}</span></div>
                <div class="detail-row"><span class="detail-label">世代</span><span class="detail-value">第 ${m.generation} 世</span></div>
                <div class="detail-row"><span class="detail-label">排行</span><span class="detail-value">${m.rank || '未记录'}</span></div>
                <div class="detail-row"><span class="detail-label">出生</span><span class="detail-value">${m.birth_date || '未记录'}</span></div>
                <div class="detail-row"><span class="detail-label">逝世</span><span class="detail-value">${m.death_date || '健在'}</span></div>
                <div class="detail-row"><span class="detail-label">职业</span><span class="detail-value">${m.profession || '未记录'}</span></div>
            </div>
            <div class="detail-avatar-area">
                ${avatarHtml}
            </div>
        </div>
        <div class="detail-body">
            <div class="detail-row"><span class="detail-label">居住地址</span><span class="detail-value">${m.address || '未记录'}</span></div>
            <div class="detail-row"><span class="detail-label">父亲</span><span class="detail-value">${fatherName}</span></div>
            <div class="detail-row"><span class="detail-label">母亲</span><span class="detail-value">${motherName}</span></div>
            <div class="detail-row"><span class="detail-label">配偶</span><span class="detail-value">${m.spouse_name || '未记录'}</span></div>
            ${spouseSection}
            <div class="detail-row" style="flex-direction:column; align-items:stretch; border-bottom:none;">
                <span class="detail-label">生平</span>
                <div class="bio-text">${m.biography || '暂无记录'}</div>
            </div>
            ${editForm}
        </div>
    `;
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function getMemberNameSync(id) {
    if (typeof allMembers !== 'undefined' && allMembers.length > 0) {
        const found = allMembers.find(m => m.id == id);
        if (found) return found.name;
    }
    return '(ID:'+id+')';
}

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            let preview = document.getElementById('avatarPreview');
            if (!preview) {
                const container = input.closest('.avatar-upload');
                if (container) {
                    preview = document.createElement('img');
                    preview.id = 'avatarPreview';
                    preview.className = 'avatar-preview';
                    container.insertBefore(preview, input);
                }
            }
            if (preview) preview.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ============================================================
// 世系图缩放功能
// ============================================================
function updateZoomDisplay() {
    const level = document.getElementById('zoomLevel');
    if (level) level.textContent = Math.round(currentZoom * 100) + '%';
}

function applyZoom() {
    const container = document.getElementById('treeContainer');
    if (!container) return;
    container.style.transform = 'scale(' + currentZoom + ')';
    container.style.transformOrigin = 'top left';
    updateZoomDisplay();
}

function zoomPedigree(direction) {
    if (direction === 'in') {
        currentZoom = Math.min(currentZoom + zoomStep, maxZoom);
    } else if (direction === 'out') {
        currentZoom = Math.max(currentZoom - zoomStep, minZoom);
    } else if (direction === 'reset') {
        currentZoom = 1.0;
    }
    applyZoom();
}

// ============================================================
// 事件监听
// ============================================================
document.addEventListener('click', function(e) {
    const trigger = e.target.closest('.branch-trigger');
    if (trigger) {
        e.preventDefault();
        e.stopPropagation();
        openBranchTab(trigger.dataset.id, trigger.dataset.name || '该成员');
    }
});

document.addEventListener('click', function(e) {
    const target = e.target.closest('.clickable');
    if (target && target.dataset.id) {
        e.preventDefault();
        openDetail(target.dataset.id);
    }
});

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-edit');
    if (btn && btn.dataset.id) {
        e.preventDefault();
        openDetail(btn.dataset.id);
    }
});

// 点击模态框外部关闭
window.addEventListener('click', function(e) {
    ['loginModal','registerModal','profileModal','addMemberModal','detailModal'].forEach(id => {
        const el = document.getElementById(id);
        if (e.target === el && el) el.style.display = 'none';
    });
});

// ============================================================
// 页面初始化
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // 初始化第一个标签
    const firstTab = document.querySelector('.tab-btn');
    if (firstTab) switchTab(firstTab);
    
    // 确保全局函数可用
    console.log('DOMContentLoaded - 初始化完成');
});

// ============================================================
// 导出为 Word 文档
// ============================================================
function exportToWord(type) {
    var content = '';
    var title = '';
    
    if (type === 'members') {
        var list = document.getElementById('memberList');
        if (list) {
            content = list.innerHTML;
            title = '<?= getClanFamilyName() ?> · 族人名录';
        } else {
            alert('未找到族人名录内容');
            return;
        }
    } else if (type === 'pedigree') {
        var container = document.getElementById('treeContainer');
        if (container) {
            var clone = container.cloneNode(true);
            var svg = clone.querySelector('.connector-svg');
            if (svg) svg.remove();
            content = clone.innerHTML;
            title = '<?= getClanFamilyName() ?> · 总世系图';
        } else {
            alert('未找到世系图内容');
            return;
        }
    } else {
        alert('未知的导出类型');
        return;
    }
    
    var styles = `
        <style>
            body { font-family: "宋体", "SimSun", serif; font-size: 12pt; padding: 40px; color: #333; }
            h1 { text-align: center; font-size: 22pt; color: #7b5d42; border-bottom: 2px solid #dac9b6; padding-bottom: 12px; margin-bottom: 20px; }
            .member-card { 
                border: 1px solid #ddd; 
                border-radius: 8px; 
                padding: 10px 14px; 
                margin-bottom: 8px; 
                background: #faf8f5;
                page-break-inside: avoid;
            }
            .member-info { display: flex; flex-wrap: wrap; gap: 4px 14px; }
            .member-name { font-weight: bold; font-size: 14pt; color: #3f2e1d; }
            .member-rank { background: #e8ddd2; padding: 0 10px; border-radius: 4px; font-size: 10pt; }
            .member-detail { color: #6f5b47; font-size: 10pt; }
            .member-children { padding-left: 30px; border-left: 2px solid #e6d7c6; margin-left: 15px; }
            .member-actions { display: none; }
            .tree-node { 
                display: inline-block; 
                text-align: center; 
                margin: 10px 15px;
                padding: 8px 12px;
                border: 2px solid #b2977d;
                border-radius: 8px;
                background: #fff;
                min-width: 60px;
            }
            .node-card { display: inline-block; }
            .card-content { text-align: center; }
            .node-name { font-weight: bold; font-size: 12pt; }
            .node-meta { font-size: 9pt; color: #8a7a6a; display: block; }
            .node-gen { font-size: 8pt; color: #b2977d; display: block; }
            .branch-trigger { display: none; }
            .children-wrapper { 
                display: flex; 
                flex-wrap: wrap; 
                justify-content: center; 
                gap: 15px; 
                padding-top: 20px; 
                margin-top: 10px;
                border-top: 2px solid #b2977d;
            }
            .tree-root { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; }
            .tree-note { text-align: center; color: #8a7a6a; font-size: 10pt; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 14px; }
            .empty-state { text-align: center; color: #7e6855; padding: 30px; }
            .flow-children { display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; padding-top: 15px; }
            .flow-node { display: inline-block; text-align: center; margin: 5px 10px; }
            .flow-card { border: 2px solid #b2977d; border-radius: 8px; padding: 6px 14px; background: #fff; }
            .flow-name { font-weight: bold; }
            .flow-meta { font-size: 9pt; color: #8a7a6a; }
        </style>
    `;
    
    var html = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>${title}</title>
            ${styles}
        </head>
        <body>
            <h1>${title}</h1>
            <p style="text-align:center;color:#8a7a6a;font-size:10pt;margin-bottom:20px;">
                导出时间：${new Date().toLocaleString()}
            </p>
            <div id="exportContent">
                ${content}
            </div>
            <p style="text-align:center;color:#8a7a6a;font-size:9pt;margin-top:30px;border-top:1px solid #ddd;padding-top:14px;">
                许氏家族 · 族谱系统 · 世代传承
            </p>
        </body>
        </html>
    `;
    
    var blob = new Blob([html], { type: 'application/msword;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var link = document.createElement('a');
    link.href = url;
    link.download = title + '.doc';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

if (typeof openPhotoViewer !== 'function') {
    window.openPhotoViewer = function(src, title, description) {
        window.location.href = '?page=photos';
    };
}

// ============================================================
// ★★★ 暴露关键函数到全局（供其他脚本调用）★★★
// ============================================================
window.openBranchTab = openBranchTab;
window.switchTab = switchTab;
window.closeTab = closeTab;
window.openDetail = openDetail;
window.showLogin = showLogin;
window.showRegister = showRegister;
window.showProfile = showProfile;
window.openAddMember = openAddMember;
window.closeAddMember = closeAddMember;
window.closeDetail = closeDetail;
window.searchMembers = searchMembers;
window.updateGeneration = updateGeneration;
window.zoomPedigree = zoomPedigree;
window.exportToWord = exportToWord;

console.log('✅ script.js 已加载，所有函数已暴露到全局');