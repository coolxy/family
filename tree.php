<?php
// 文件: tree.php - 世系图数据构建与渲染
require_once 'config.php';

// ============================================================
// 构建树状数据
// ============================================================
// 文件: tree.php - 修改 buildTreeData 函数
function buildTreeData($members) {
    $childMap = [];
    $nameMap = [];
    
    foreach ($members as $m) {
        $nameMap[$m['id']] = $m['name'];
        $gen = intval($m['generation'] ?? 1);
        if (!isset($generationGroups[$gen])) {
            $generationGroups[$gen] = [];
        }
        $generationGroups[$gen][] = $m;
    }
    
    ksort($generationGroups);
    
    // ★★★ 根据父节点性别决定使用 father_id 还是 mother_id ★★★
    foreach ($members as $m) {
        // 如果有父亲
        if ($m['father_id']) {
            // 检查父亲是否是女性（如果是女性，说明数据有误，应该使用 mother_id）
            $fatherMember = null;
            foreach ($members as $p) {
                if ($p['id'] == $m['father_id']) {
                    $fatherMember = $p;
                    break;
                }
            }
            // 如果父亲是女性，则改用 mother_id
            if ($fatherMember && $fatherMember['gender'] == '女') {
                if (!isset($childMap[$m['father_id']])) {
                    $childMap[$m['father_id']] = [];
                }
                $childMap[$m['father_id']][] = $m['id'];
            } else {
                if (!isset($childMap[$m['father_id']])) {
                    $childMap[$m['father_id']] = [];
                }
                $childMap[$m['father_id']][] = $m['id'];
            }
        }
        // 如果有母亲且没有父亲（或者父亲是女性已处理）
        elseif ($m['mother_id']) {
            if (!isset($childMap[$m['mother_id']])) {
                $childMap[$m['mother_id']] = [];
            }
            $childMap[$m['mother_id']][] = $m['id'];
        }
    }
    
    // 找根节点
    $roots = [];
    foreach ($members as $m) {
        if ($m['generation'] == 1 || (!$m['father_id'] && !$m['mother_id'])) {
            $roots[] = $m['id'];
        }
    }
    
    if (empty($roots) && !empty($members)) {
        $minGen = min(array_keys($generationGroups));
        foreach ($members as $m) {
            if ($m['generation'] == $minGen) {
                $roots[] = $m['id'];
            }
        }
    }
    
    $treeRoots = [];
    foreach ($roots as $rid) {
        $treeRoots[] = buildTree($rid, $childMap, $nameMap);
    }
    return $treeRoots;
}

function buildTree($id, $childMap, $nameMap) {
    $node = [
        'id' => $id,
        'name' => $nameMap[$id] ?? '未知',
        'children' => []
    ];
    if (isset($childMap[$id])) {
        foreach ($childMap[$id] as $childId) {
            $node['children'][] = buildTree($childId, $childMap, $nameMap);
        }
    }
    return $node;
}

// ============================================================
// 构建支系树（以某成员为根）- 修复版
// ============================================================
function buildBranchTree($rootId, $members) {
    // ★★★ 构建父子关系映射 ★★★
    $childMap = [];
    $nameMap = [];
    
    foreach ($members as $m) {
        $nameMap[$m['id']] = $m['name'];
        
        // ★★★ 关键修复：同时支持 father_id 和 mother_id ★★★
        // 如果是女性，使用 mother_id 作为父节点
        // 否则使用 father_id
        
        // 先检查 father_id
        if ($m['father_id'] && $m['father_id'] > 0) {
            // 检查父亲是否是女性
            $fatherIsFemale = false;
            foreach ($members as $p) {
                if ($p['id'] == $m['father_id'] && $p['gender'] == '女') {
                    $fatherIsFemale = true;
                    break;
                }
            }
            // 如果父亲是女性，则使用 mother_id
            if ($fatherIsFemale && $m['mother_id'] && $m['mother_id'] > 0) {
                if (!isset($childMap[$m['mother_id']])) {
                    $childMap[$m['mother_id']] = [];
                }
                $childMap[$m['mother_id']][] = $m['id'];
            } else {
                if (!isset($childMap[$m['father_id']])) {
                    $childMap[$m['father_id']] = [];
                }
                $childMap[$m['father_id']][] = $m['id'];
            }
        } 
        // 如果没有 father_id，检查 mother_id
        elseif ($m['mother_id'] && $m['mother_id'] > 0) {
            if (!isset($childMap[$m['mother_id']])) {
                $childMap[$m['mother_id']] = [];
            }
            $childMap[$m['mother_id']][] = $m['id'];
        }
    }
    
    // ★★★ 检查根节点是否有子节点 ★★★
    $hasChildren = isset($childMap[$rootId]) && !empty($childMap[$rootId]);
    
    // ★★★ 递归构建树 ★★★
    function buildTreeRecursive($id, $childMap, $nameMap) {
        $node = [
            'id' => $id,
            'name' => $nameMap[$id] ?? '未知',
            'children' => []
        ];
        
        if (isset($childMap[$id])) {
            foreach ($childMap[$id] as $childId) {
                $node['children'][] = buildTreeRecursive($childId, $childMap, $nameMap);
            }
        }
        
        return $node;
    }
    
    return buildTreeRecursive($rootId, $childMap, $nameMap);
}

// 文件: tree.php - renderTreeNode 函数（确保包含支系图图标）
function renderTreeNode($node, $members) {
    $memberData = null;
    foreach ($members as $m) {
        if ($m['id'] == $node['id']) {
            $memberData = $m;
            break;
        }
    }
    $birth = $memberData ? htmlspecialchars($memberData['birth_date'] ?: '') : '';
    $death = $memberData ? htmlspecialchars($memberData['death_date'] ?: '') : '';
    $rank = $memberData ? htmlspecialchars($memberData['rank'] ?: '') : '';
    $generation = $memberData ? intval($memberData['generation'] ?? 1) : 1;
    $gender = $memberData ? htmlspecialchars($memberData['gender']) : '';
    $displayName = $node['name'] . ($rank ? ' (' . $rank . ')' : '') . ($gender == '女' ? ' 🚺' : '');
    $hasChildren = !empty($node['children']);
    
    $html = '<div class="tree-node" data-id="' . $node['id'] . '" data-gen="' . $generation . '">';
    $html .= '<div class="node-card">';
    $html .= '<div class="card-content clickable" data-id="' . $node['id'] . '" data-birth="' . $birth . '" data-death="' . $death . '">';
    $html .= '<span class="node-name">' . htmlspecialchars($displayName) . '</span>';
    $html .= '<span class="node-meta">' . ($birth ?: '?') . ($death ? '～' . $death : '') . '</span>';
    $html .= '<span class="node-gen">第' . $generation . '世</span>';
    $html .= '</div>';
    // ★★★ 支系图图标 ★★★
    if ($hasChildren) {
        $html .= '<span class="branch-trigger" data-id="' . $node['id'] . '" data-name="' . htmlspecialchars($node['name']) . '" title="查看以 ' . htmlspecialchars($node['name']) . ' 为始祖的支系图">🌿</span>';
    }
    $html .= '</div>';
    if ($hasChildren) {
        $html .= '<div class="children-wrapper" data-parent="' . $node['id'] . '">';
        foreach ($node['children'] as $child) {
            $html .= renderTreeNode($child, $members);
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

// 文件: tree.php - 支系图节点渲染
function renderBranchTreeAsNodes($node, $members) {
    $memberData = null;
    foreach ($members as $m) {
        if ($m['id'] == $node['id']) { $memberData = $m; break; }
    }
    $birth = $memberData ? htmlspecialchars($memberData['birth_date'] ?: '') : '';
    $death = $memberData ? htmlspecialchars($memberData['death_date'] ?: '') : '';
    $rank = $memberData ? htmlspecialchars($memberData['rank'] ?: '') : '';
    $generation = $memberData ? intval($memberData['generation'] ?? 1) : 1;
    $displayName = $node['name'] . ($rank ? ' (' . $rank . ')' : '');
    $hasChildren = !empty($node['children']);
    
    $html = '<div class="tree-node" data-id="' . $node['id'] . '" data-gen="' . $generation . '">';
    $html .= '<div class="node-card">';
    $html .= '<div class="card-content clickable" data-id="' . $node['id'] . '" data-birth="' . $birth . '" data-death="' . $death . '">';
    $html .= '<span class="node-name">' . htmlspecialchars($displayName) . '</span>';
    $html .= '<span class="node-meta">' . ($birth ?: '?') . ($death ? '～' . $death : '') . '</span>';
    $html .= '<span class="node-gen">第' . $generation . '世</span>';
    $html .= '</div>';
    $html .= '</div>';
    if ($hasChildren) {
        $html .= '<div class="children-wrapper" data-parent="' . $node['id'] . '">';
        foreach ($node['children'] as $child) {
            $html .= renderBranchTreeAsNodes($child, $members);
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

// ============================================================
// 渲染层级成员列表（族人名录）
// ============================================================
// 文件: tree.php - 修改 renderMemberHierarchy 函数
function renderMemberHierarchy($members, $parentId = 0) {
    $html = '';
    $children = [];
    
    foreach ($members as $m) {
        // ★★★ 关键修改：如果父节点是女性，使用 mother_id 关联 ★★★
        $parentMember = null;
        foreach ($members as $p) {
            if ($p['id'] == $parentId) {
                $parentMember = $p;
                break;
            }
        }
        
        // 如果父节点是女性，子节点通过 mother_id 关联
        if ($parentMember && $parentMember['gender'] == '女') {
            $pid = $m['mother_id'];
        } else {
            // 否则优先使用 father_id，其次使用 mother_id
            $pid = $m['father_id'] ?: $m['mother_id'];
        }
        
        if ($pid == $parentId) {
            $children[] = $m;
        }
    }
    
    // ... 其余代码保持不变 ...
    
    // ★★★ 修改：正确显示父亲和母亲 ★★★
    foreach ($children as $m) {
        $hasChildren = false;
        foreach ($members as $mm) {
            // 检查当前节点是否有子节点（同样考虑性别）
            $parentMember = null;
            foreach ($members as $p) {
                if ($p['id'] == $m['id']) {
                    $parentMember = $p;
                    break;
                }
            }
            if ($parentMember && $parentMember['gender'] == '女') {
                $pid = $mm['mother_id'];
            } else {
                $pid = $mm['father_id'] ?: $mm['mother_id'];
            }
            if ($pid == $m['id']) {
                $hasChildren = true;
                break;
            }
        }
        
        $avatar = $m['avatar'] ? '<img src="' . htmlspecialchars($m['avatar']) . '" class="member-avatar" onerror="this.style.display=\'none\'">' : '';
        
        // ★★★ 正确显示父亲和母亲 ★★★
        $fatherDisplay = getMemberName($m['father_id']) ?: '未知';
        // 如果有 mother_id，优先使用 mother_id 获取姓名
        $motherDisplay = $m['mother_id'] ? getMemberName($m['mother_id']) : ($m['mother_name'] ?: '未知');
        
        $html .= '<div class="member-card">';
        $html .= '<div class="member-info">';
        $html .= $avatar;
        $html .= '<span class="member-name clickable" data-id="' . $m['id'] . '" 
                  data-birth="' . htmlspecialchars($m['birth_date'] ?: '') . '"
                  data-death="' . htmlspecialchars($m['death_date'] ?: '') . '"
                  data-gender="' . htmlspecialchars($m['gender']) . '"
                  data-generation="' . htmlspecialchars($m['generation']) . '">' . htmlspecialchars($m['name']) . '</span>';
        if ($m['rank']) $html .= '<span class="member-rank">' . htmlspecialchars($m['rank']) . '</span>';
        $html .= '<span class="member-detail">#' . $m['id'] . ' · ' . $m['gender'] . ' · 世' . $m['generation'] . '</span>';
        $html .= '<span class="member-detail">📅 ' . ($m['birth_date'] ?: '?') . ($m['death_date'] ? '～' . $m['death_date'] : '') . '</span>';
        $html .= '<span class="member-detail">父: ' . $fatherDisplay . ' · 母: ' . $motherDisplay . '</span>';
        if ($m['profession']) $html .= '<span class="member-detail">💼 ' . htmlspecialchars($m['profession']) . '</span>';
        $html .= '</div>';
        
        $html .= '<div class="member-actions">';
        $html .= '<button class="btn-sm btn-edit" data-id="' . $m['id'] . '">✎ 编辑</button>';
        $html .= '<form method="post" action="member_actions.php" onsubmit="return confirm(\'确认删除？\')" style="display:inline;">';
        $html .= '<input type="hidden" name="action" value="delete">';
        $html .= '<input type="hidden" name="id" value="' . $m['id'] . '">';
        $html .= '<input type="hidden" name="return_page" value="members">';
        $html .= '<button type="submit" class="btn-sm btn-danger">删除</button>';
        $html .= '</form>';
        $html .= '</div>';
        $html .= '</div>';
        
        if ($hasChildren) {
            $html .= '<div class="member-children">';
            $html .= renderMemberHierarchy($members, $m['id']);
            $html .= '</div>';
        }
    }
    return $html;
}
?>