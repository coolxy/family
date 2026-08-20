<?php
// 文件: pages/pedigree.php - 世系图（修复 content.appendChild 错误）
$members = getAllMembers();
$treeRoots = buildTreeData($members);
$loggedIn = isLoggedIn();
?>
<div class="page-header">
    <div class="pedigree-header-row">
        <h2>🌳 总世系图</h2>
        <div class="pedigree-header-hint">
            🖱 滚轮缩放 · 拖拽平移 · 点击姓名查看详情 · 点击 🌿 查看支系图
        </div>
        <div class="pedigree-header-actions">
            <?php if ($loggedIn): ?>
                <button class="btn" onclick="parent.openAddMember()">➕ 添丁入谱</button>
            <?php endif; ?>
            <button class="btn" onclick="exportD3ToPDF()">📄 导出 PDF</button>
        </div>
    </div>
</div>
<!-- 世系图容器 -->
<div class="pedigree-section">
    <div class="tree-wrapper-full" id="d3TreeWrapper" style="width:100%;min-height:600px;height:calc(100vh - 220px);border:1px solid #e6d7c6;border-radius:12px;background:#fcf7f2;overflow:hidden;position:relative;">
        <div id="d3TreeContainer" style="width:100%;height:100%;"></div>
        <div style="position:absolute;bottom:12px;left:50%;transform:translateX(-50%);background:rgba(255,255,255,0.85);padding:4px 16px;border-radius:20px;font-size:0.75rem;color:#8a7a6a;border:1px solid #e6d7c6;pointer-events:none;z-index:10;">
            🖱 滚轮缩放 · 拖拽平移 · 点击姓名查看详情 · 点击 🌿 查看支系图
        </div>
    </div>
</div>
<!-- 导出PDF的iframe（隐藏） -->
<iframe id="pdfExportFrame" style="display:none;"></iframe>
<style>
.pedigree-header-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #dacfc2;
    margin-bottom: 16px;
}
.pedigree-header-row h2 {
    font-weight: 300;
    color: #5f4a34;
    font-size: 1.6rem;
    letter-spacing: 2px;
    margin: 0;
    border: none;
    padding: 0;
    flex-shrink: 0;
}
.pedigree-header-hint {
    font-size: 0.85rem;
    color: #8a7a6a;
    flex: 1;
    text-align: center;
    min-width: 150px;
}
.pedigree-header-actions {
    flex-shrink: 0;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.pedigree-header-actions .btn {
    padding: 6px 16px;
    font-size: 0.85rem;
}
.pedigree-section {
    width: 100%;
}
.tree-wrapper-full {
    width: 100%;
    min-height: 600px;
    height: calc(100vh - 220px);
    border: 1px solid #e6d7c6;
    border-radius: 12px;
    background: #fcf7f2;
    overflow: hidden;
    position: relative;
}
.link {
    fill: none;
    stroke: #b2977d;
    stroke-width: 2.5px;
    stroke-opacity: 0.8;
}
.node-card-bg {
    fill: #ffffff;
    stroke: #b2977d;
    stroke-width: 2.5px;
    rx: 10px;
    ry: 10px;
    stroke-opacity: 0.8;
}
.node-name {
    font-weight: 700;
    font-size: 14px;
    fill: #3f2e1d;
    text-anchor: middle;
    pointer-events: none;
}
.node-meta {
    font-size: 11px;
    fill: #8a7a6a;
    text-anchor: middle;
    pointer-events: none;
}
.node-gen {
    font-size: 10px;
    fill: #b2977d;
    text-anchor: middle;
    pointer-events: none;
}
.branch-icon-d3 {
    font-size: 18px;
    cursor: pointer;
    pointer-events: all;
}
.branch-panel-container {
    width: 100%;
    height: 100%;
    min-height: 500px;
    position: relative;
}
@media (max-width: 700px) {
    .pedigree-header-row {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    .pedigree-header-hint {
        text-align: center;
        font-size: 0.75rem;
        order: 3;
    }
    .pedigree-header-actions {
        align-self: center;
    }
    .pedigree-header-actions .btn {
        font-size: 0.75rem;
        padding: 4px 12px;
    }
    .tree-wrapper-full {
        height: 450px !important;
        min-height: 350px !important;
    }
    .branch-panel-container {
        min-height: 350px;
    }
    .node-card-bg {
        rx: 8px;
        ry: 8;
    }
    .node-name {
        font-size: 12px;
    }
    .node-meta {
        font-size: 9px;
    }
    .node-gen {
        font-size: 8px;
    }
    .branch-icon-d3 {
        font-size: 15px;
    }
}
</style>
<script src="js/d3.v7.min.js"></script>
<script>
// ============================================================
// 世系图 JavaScript（修复 content.appendChild 错误）
// ============================================================
(function() {
    'use strict';
    
    // 全局数据
    var treeData = <?= json_encode($treeRoots) ?>;
    var allMembers = <?= json_encode($members) ?>;
    var loggedIn = <?= $loggedIn ? 'true' : 'false' ?>;
    
    // 查找成员数据
    function findMemberData(id) {
        if (!allMembers) return null;
        for (var i = 0; i < allMembers.length; i++) {
            if (allMembers[i].id == id) return allMembers[i];
        }
        return null;
    }
    
    // ============================================================
    // 节点点击处理
    // ============================================================
    function onNodeClick(d) {
        if (!d || !d.data || d.data.isVirtual || !d.data.id) return;
        
        if (d.data.isBranch) {
            if (typeof window.openBranchTab === 'function') {
                window.openBranchTab(d.data.id, d.data.name);
            }
            return;
        }
        
        if (typeof window.openDetail === 'function') {
            window.openDetail(d.data.id);
        }
    }
    
    // ============================================================
    // 构建层级数据
    // ============================================================
    function buildHierarchy(nodes, isBranchMode) {
        var result = [];
        for (var i = 0; i < nodes.length; i++) {
            var node = nodes[i];
            var memberData = findMemberData(node.id);
            var item = {
                id: node.id,
                name: node.name || '未知',
                rank: memberData ? (memberData.rank || '') : '',
                birth: memberData ? (memberData.birth_date || '') : '',
                death: memberData ? (memberData.death_date || '') : '',
                generation: memberData ? (memberData.generation || 1) : 1,
                gender: memberData ? (memberData.gender || '') : '',
                isBranch: isBranchMode || false,
                children: []
            };
            if (node.children && node.children.length > 0) {
                item.children = buildHierarchy(node.children, isBranchMode);
            }
            result.push(item);
        }
        return result;
    }
    
    // ============================================================
    // ★★★ 渲染D3树 ★★★
    // ============================================================
    function renderD3Tree(containerId, data, isBranchMode) {
        var container = document.getElementById(containerId);
        if (!container) {
            console.error('容器不存在:', containerId);
            return;
        }
        
        // 获取容器尺寸
        var width = container.clientWidth || 800;
        var height = container.clientHeight || 600;
        
        console.log('渲染D3树:', containerId, '尺寸:', width, 'x', height, 'isBranchMode:', isBranchMode);
        
        // 清空容器
        container.innerHTML = '';
        
        if (!data || data.length === 0) {
            container.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#8a7a6a;font-size:1.1rem;">暂无数据</div>';
            return;
        }
        
        var rootData = buildHierarchy(data, isBranchMode);
        
        if (rootData.length === 0) {
            container.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#8a7a6a;font-size:1.1rem;">暂无数据</div>';
            return;
        }
        
        // 构建根节点
        var root;
        if (rootData.length === 1) {
            root = rootData[0];
        } else {
            root = {
                id: 0,
                name: '始祖',
                generation: 0,
                children: rootData,
                isVirtual: true
            };
        }
        
        // 边距（左侧加宽防止导出裁切）
        var margin = { top: 60, right: 60, bottom: 60, left: 180 };
        var innerWidth = width - margin.left - margin.right;
        var innerHeight = height - margin.top - margin.bottom;
        
        // 创建SVG
        var svg = d3.select(container)
            .append('svg')
            .attr('width', width)
            .attr('height', height)
            .style('display', 'block')
            .style('background', '#fcf7f2');
        
        // 主组（用于缩放）
        var g = svg.append('g')
            .attr('transform', 'translate(' + margin.left + ',' + margin.top + ')');
        
        // 缩放功能
        var zoom = d3.zoom()
            .scaleExtent([0.1, 3])
            .on('zoom', function(event) {
                g.attr('transform', event.transform);
            });
        
        svg.call(zoom);
        
        // ★★★ 创建D3树布局 ★★★
        var treeLayout = d3.tree()
            .size([innerWidth, innerHeight])
            .separation(function(a, b) {
                return (a.parent == b.parent ? 1.8 : 2.2);
            })
            .nodeSize([150, 100]);
        
        // 构建层级结构
        var rootHierarchy = d3.hierarchy(root, function(d) {
            return d.children && d.children.length > 0 ? d.children : null;
        });
        
        // 计算树布局
        var treeDataLayout = treeLayout(rootHierarchy);
        
        var descendants = treeDataLayout.descendants();
        var links = treeDataLayout.links();
        
        console.log('树节点数:', descendants.length);
        console.log('树连接数:', links.length);
        
        // 自动缩放适应容器
        var finalScale = 1;
        if (descendants.length > 0) {
            var minX = d3.min(descendants, function(d) { return d.x; });
            var maxX = d3.max(descendants, function(d) { return d.x; });
            var minY = d3.min(descendants, function(d) { return d.y; });
            var maxY = d3.max(descendants, function(d) { return d.y; });
            
            var totalWidth = (maxX - minX) + 150;
            var totalHeight = (maxY - minY) + 150;
            
            var scaleX = innerWidth / totalWidth;
            var scaleY = innerHeight / totalHeight;
            finalScale = Math.min(scaleX, scaleY, 1);
            finalScale = Math.max(finalScale, 0.4);
            
            if (finalScale < 1) {
                var centerX = (minX + maxX) / 2;
                var centerY = (minY + maxY) / 2;
                var translateX = innerWidth / 2 - centerX * finalScale;
                var translateY = innerHeight / 2 - centerY * finalScale;
                
                svg.call(zoom.transform, d3.zoomIdentity
                    .translate(translateX + margin.left, translateY + margin.top)
                    .scale(finalScale)
                );
            }
        }
        
        // ============================================================
        // ★★★ 绘制连接线 ★★★
        // ============================================================
        var linkGenerator = d3.linkVertical()
            .x(function(d) { return d.x; })
            .y(function(d) { return d.y; });
        
        var linkGroup = g.append('g')
            .attr('class', 'links');
        
        linkGroup.selectAll('path')
            .data(links)
            .enter()
            .append('path')
            .attr('class', 'link')
            .attr('d', linkGenerator)
            .attr('fill', 'none')
            .attr('stroke', '#b2977d')
            .attr('stroke-width', 2.5);
        
        // ============================================================
        // ★★★ 绘制节点 ★★★
        // ============================================================
        var cardWidth = 140;
        var cardHeight = 80;
        
        var nodeGroup = g.append('g')
            .attr('class', 'nodes')
            .selectAll('.node')
            .data(descendants)
            .enter()
            .append('g')
            .attr('class', function(d) { 
                return 'node' + (d.data.isVirtual ? ' virtual-node' : ''); 
            })
            .attr('transform', function(d) { 
                return 'translate(' + d.x + ',' + d.y + ')'; 
            })
            .style('cursor', function(d) { 
                return d.data.isVirtual ? 'default' : 'pointer'; 
            });
        
        // 节点点击事件
        nodeGroup.on('click', function(event, d) {
            if (!d.data.isVirtual && d.data.id) {
                onNodeClick(d);
            }
        });
        
        // 卡片背景
        nodeGroup.append('rect')
            .attr('class', 'node-card-bg')
            .attr('x', -cardWidth / 2)
            .attr('y', -cardHeight / 2)
            .attr('width', cardWidth)
            .attr('height', cardHeight)
            .attr('fill', function(d) { 
                return d.data.isVirtual ? 'transparent' : '#ffffff'; 
            })
            .attr('stroke', function(d) { 
                return d.data.isVirtual ? 'transparent' : '#b2977d'; 
            })
            .attr('stroke-width', 2.5)
            .attr('rx', 10)
            .attr('ry', 10);
        
        // 姓名
        nodeGroup.append('text')
            .attr('class', 'node-name')
            .attr('text-anchor', 'middle')
            .attr('y', -10)
            .text(function(d) {
                if (d.data.isVirtual) return '...';
                var name = d.data.name || '';
                if (d.data.rank) name += ' (' + d.data.rank + ')';
                return name;
            });
        
        // 生卒
        nodeGroup.append('text')
            .attr('class', 'node-meta')
            .attr('text-anchor', 'middle')
            .attr('y', 14)
            .text(function(d) {
                if (d.data.isVirtual) return '';
                var birth = d.data.birth || '?';
                var death = d.data.death || '';
                return birth + (death ? '～' + death : '');
            });
        
        // 世代
        nodeGroup.append('text')
            .attr('class', 'node-gen')
            .attr('text-anchor', 'middle')
            .attr('y', 30)
            .text(function(d) {
                if (d.data.isVirtual || !d.data.generation) return '';
                return '第' + d.data.generation + '世';
            });
        
        // 支系图标
        nodeGroup.filter(function(d) {
            return !d.data.isVirtual && d.data.children && d.data.children.length > 0;
        }).append('text')
            .attr('class', 'branch-icon-d3')
            .attr('x', cardWidth / 2 + 12)
            .attr('y', -8)
            .style('font-size', '18px')
            .style('cursor', 'pointer')
            .style('pointer-events', 'all')
            .text('🌿')
            .on('click', function(event, d) {
                event.stopPropagation();
                if (typeof window.openBranchTab === 'function') {
                    window.openBranchTab(d.data.id, d.data.name);
                }
            })
            .on('mouseover', function() {
                d3.select(this).style('font-size', '22px');
            })
            .on('mouseout', function() {
                d3.select(this).style('font-size', '18px');
            });
        
        console.log('✅ 渲染完成，节点数:', nodeGroup.size());
    }
    
    // ============================================================
    // 渲染总世系图
    // ============================================================
    function renderD3TreeMain() {
        const container = document.getElementById('d3TreeContainer');
        if (!container) {
            setTimeout(renderD3TreeMain, 200);
            return;
        }
        if(container.clientWidth < 10 || container.clientHeight < 10) {
            setTimeout(renderD3TreeMain, 300);
            return;
        }
        renderD3Tree('d3TreeContainer', treeData, false);
    }
    
    // ============================================================
    // 导出PDF
    // ============================================================
    function exportD3ToPDF() {
        <?php if (!$loggedIn): ?>
            if (typeof window.showLogin === 'function') {
                window.showLogin();
            } else {
                alert('请先登录');
            }
            return;
        <?php endif; ?>

        const wrapDom = document.getElementById('d3TreeContainer');
        if (!wrapDom) {
            alert('图表还未加载完成，请等待渲染完毕再导出');
            return;
        }
        const targetSvg = wrapDom.querySelector('svg');
        if (!targetSvg) {
            alert('世系图渲染未完成，刷新页面重试');
            return;
        }

        try {
            const svgCopy = targetSvg.cloneNode(true);
            const gWrap = svgCopy.querySelector('g');
            if (gWrap) {
                const oldTransform = gWrap.getAttribute('transform') || '';
                gWrap.setAttribute('transform', oldTransform + ' translate(60,60)');
            }
            const svgText = svgCopy.outerHTML;
            const printTime = new Date().toLocaleString();

            const printPage =
                '<!DOCTYPE html><html lang="zh-CN"><head>' +
                '<meta charset="UTF-8">' +
                '<title>许氏家族世系图</title>' +
                '<style>' +
                '*{margin:0;padding:0;box-sizing:border-box;}' +
                'body{font-family:"宋体",SimSun,serif;padding:30px 50px;background:#fff;display:flex;flex-direction:column;align-items:center;min-height:100vh;}' +
                'h1{text-align:center;color:#7b5d42;border-bottom:2px solid #dac9b6;padding-bottom:10px;margin-bottom:12px;font-size:22pt;font-weight:300;letter-spacing:4px;width:100%;}' +
                '.time-text{text-align:center;color:#8a7a6a;font-size:10pt;margin-bottom:16px;width:100%;}' +
                '.svg-box{width:100%;overflow:visible;display:flex;justify-content:center;padding-left:20px;}' +
                'svg{max-width:100%;height:auto;background:#fcf7f2;border-radius:8px;overflow:visible !important;}' +
                '.link{stroke:#b2977d;stroke-width:2;fill:none;}' +
                '.node-card-bg{fill:#ffffff;stroke:#b2977d;stroke-width:2;rx:8;ry:8;}' +
                '.node-name{font-weight:700;font-size:13px;fill:#3f2e1d;text-anchor:middle;}' +
                '.node-meta{font-size:10px;fill:#8a7a6a;text-anchor:middle;}' +
                '.node-gen{font-size:9px;fill:#b2977d;text-anchor:middle;}' +
                '.foot-tip{text-align:center;color:#8a7a6a;font-size:9pt;margin-top:20px;border-top:1px solid #ddd0c0;padding-top:12px;width:100%;}' +
                '@media print{body{padding:10mm 30mm !important;}.svg-box{overflow:visible !important;}svg{page-break-inside:avoid;}@page{size:A4 landscape;margin:20mm 25mm;}}' +
                '</style></head><body>' +
                '<h1><?= getClanFamilyName() ?> · 总世系图</h1>' +
                '<div class="time-text">导出时间：' + printTime + '</div>' +
                '<div class="svg-box">' + svgText + '</div>' +
                '<div class="foot-tip"><?= getClanFamilyName() ?> · 血脉传承 · 族脉永续</div>' +
                '</body></html>';

            const blob = new Blob([printPage], { type: 'text/html;charset=utf-8' });
            const fileUrl = URL.createObjectURL(blob);
            const newWin = window.open(fileUrl, '_blank');

            if (!newWin || newWin.closed || typeof newWin.closed == 'undefined') {
                alert('浏览器弹窗拦截，请允许本站弹出窗口后重试导出');
                URL.revokeObjectURL(fileUrl);
                return;
            }

            newWin.onload = function () {
                setTimeout(function () {
                    try {
                        newWin.print();
                    } catch (err) {
                        alert('自动打印失败，请在新页面右键选择打印，纸张设置A4横向');
                    }
                }, 1800);
            };
        } catch (err) {
            console.error('导出PDF捕获错误：', err);
            alert('导出失败：' + err.message + '，请刷新页面重新操作');
        }
    }
    
    // ============================================================
    // ★★★ 支系图功能 ★★★
    // ============================================================
    var branchTabCounter = 0;
    
    function openBranchTab(memberId, memberName) {
        var existingTab = document.querySelector('.tab-btn[data-branch="' + memberId + '"]');
        if (existingTab) { switchTab(existingTab); return; }
        
        branchTabCounter++;
        var tabId = 'branch-' + branchTabCounter;
        var tabBar = document.getElementById('tabBar');
        if (!tabBar) return;
        
        var newTab = document.createElement('button');
        newTab.className = 'tab-btn branch-tab';
        newTab.dataset.tab = tabId;
        newTab.dataset.branch = memberId;
        newTab.innerHTML = '🌿 ' + memberName + ' 支系 <span class="tab-close" onclick="event.stopPropagation();closeTab(this, \'' + tabId + '\')">×</span>';
        tabBar.appendChild(newTab);
        
        var contentArea = document.getElementById('tabContent');
        if (!contentArea) return;
        
        var panel = document.createElement('div');
        panel.id = 'tab-' + tabId;
        panel.className = 'tab-panel';
        panel.innerHTML = '<div class="branch-panel-container" id="branchPanel_' + tabId + '" style="width:100%;height:100%;min-height:500px;position:relative;">' +
                          '<div id="branchContainer_' + tabId + '" style="width:100%;height:100%;min-height:500px;"></div>' +
                          '</div>';
        
        // ★★★ 修复：使用 contentArea.appendChild，不是 content.appendChild ★★★
        contentArea.appendChild(panel);
        
        var containerId = 'branchContainer_' + tabId;
        
        // 显示加载状态
        var container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#8a7a6a;font-size:1.1rem;">加载支系图中...</div>';
        }
        
        // ★★★ 获取支系图数据 ★★★
        fetch('member_actions.php?action=branch&id=' + memberId)
            .then(function(res) { 
                if (!res.ok) throw new Error('网络请求失败');
                return res.json(); 
            })
            .then(function(data) {
                console.log('支系图数据返回:', data);
                
                if (data && data.branchData) {
                    var containerEl = document.getElementById(containerId);
                    if (containerEl) {
                        containerEl._d3Data = data.branchData;
                    }
                    
                    setTimeout(function() {
                        renderD3Tree(containerId, data.branchData, true);
                    }, 100);
                    
                    setTimeout(function() {
                        renderD3Tree(containerId, data.branchData, true);
                    }, 500);
                    
                } else if (data && data.html) {
                    panel.innerHTML = data.html;
                } else {
                    panel.innerHTML = '<div class="empty-state">加载支系图失败：数据格式错误</div>';
                }
            })
            .catch(function(err) {
                console.error('加载支系图出错:', err);
                panel.innerHTML = '<div class="empty-state">加载失败，请重试: ' + err.message + '</div>';
            });
        
        switchTab(newTab);
    }
    
    function switchTab(tabBtn) {
        document.querySelectorAll('.tab-btn').forEach(function(btn) { btn.classList.remove('active'); });
        tabBtn.classList.add('active');
        document.querySelectorAll('.tab-panel').forEach(function(panel) { panel.classList.remove('active'); });
        var target = tabBtn.dataset.tab;
        var panel = document.getElementById('tab-' + target);
        if (panel) panel.classList.add('active');
        
        if (target && target.indexOf('branch-') === 0) {
            var containerId = 'branchContainer_' + target;
            var container = document.getElementById(containerId);
            if (container && container._d3Data) {
                setTimeout(function() {
                    renderD3Tree(containerId, container._d3Data, true);
                }, 200);
            }
        }
    }
    
    function closeTab(closeBtn, tabId) {
        var tabBtn = closeBtn.closest('.tab-btn');
        var panel = document.getElementById('tab-' + tabId);
        if (tabBtn) tabBtn.remove();
        if (panel) panel.remove();
        var firstTab = document.querySelector('.tab-btn');
        if (firstTab) switchTab(firstTab);
    }
    
    document.addEventListener('click', function(e) {
        var tabBtn = e.target.closest('.tab-btn');
        if (tabBtn && !e.target.closest('.tab-close')) {
            switchTab(tabBtn);
        }
    });
    
    // ============================================================
    // 页面初始化
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(renderD3TreeMain, 300);
        setTimeout(renderD3TreeMain, 600);
        setTimeout(renderD3TreeMain, 1000);
        
        var resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(renderD3TreeMain, 300);
        });
    });
    
    // ============================================================
    // 暴露函数到全局
    // ============================================================
    window.renderD3TreeMain = renderD3TreeMain;
    window.renderD3Tree = renderD3Tree;
    window.exportD3ToPDF = exportD3ToPDF;
    window.openBranchTab = openBranchTab;
    window.switchTab = switchTab;
    window.closeTab = closeTab;
    window.onNodeClick = onNodeClick;
    window.findMemberData = findMemberData;
    
    console.log('✅ pages/pedigree.php 已加载');
    
})();
</script>