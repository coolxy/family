<?php
// 文件: pages/summary.php - 家族综述
$clanSummary = getClanSummary();
$loggedIn = isLoggedIn();
?>
<!-- 移除 <div class="page-header"><h2>📖 家族综述</h2></div> -->
<div class="clan-summary" style="border:none;background:transparent;padding:0;">
    <div class="summary-content" style="font-size:1.1rem;line-height:2;"><?= nl2br(htmlspecialchars($clanSummary)) ?></div>
    <?php if ($loggedIn): ?>
        <button class="btn-sm btn-edit-summary" onclick="document.getElementById('summaryEdit2').style.display='block'">✎ 编辑综述</button>
        <div id="summaryEdit2" style="display:none; margin-top:10px;">
            <form method="post" action="member_actions.php">
                <input type="hidden" name="action" value="update_summary">
                <input type="hidden" name="return_page" value="summary">
                <textarea name="content" rows="5" style="width:100%; padding:10px; border-radius:12px; border:1px solid #dacfc2;"><?= htmlspecialchars($clanSummary) ?></textarea>
                <button type="submit" class="btn" style="margin-top:8px;">保存综述</button>
                <button type="button" class="btn-sm" style="margin-top:8px;" onclick="document.getElementById('summaryEdit2').style.display='none'">取消</button>
            </form>
        </div>
    <?php endif; ?>
</div>