<?php $__env->startSection('title', 'Messages'); ?>

<?php $__env->startSection('content'); ?>

<style>
    body, .content-wrapper, main, [class*="content"] {
        background: transparent !important;
    }
</style>

<div style="min-height:100vh; background: linear-gradient(160deg, #eef4ff 0%, #f5f8ff 40%, #ffffff 100%); margin:-1rem -1.25rem; padding:1rem 1.25rem;">
<div style="max-width:660px; margin:0 auto; padding:2rem 0; font-family:inherit;">

    
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.75rem;">
        <div>
            <h1 style="font-size:18px; font-weight:600; color:#111; margin:0 0 2px; letter-spacing:-0.2px;">Messages</h1>
            <p style="font-size:12px; color:#aaa; margin:0;">Select a conversation</p>
        </div>
        <div style="text-align:right;">
            <p style="font-size:13px; font-weight:500; color:#111; margin:0 0 1px;"><?php echo e(auth()->user()->name); ?></p>
            <p style="font-size:12px; color:#bbb; margin:0;"><?php echo e(auth()->user()->email); ?></p>
        </div>
    </div>

    <?php if(session('error')): ?>
    <div style="margin-bottom:1rem; padding:10px 14px; background:#fef2f2; border:0.5px solid #fecaca; color:#b91c1c; border-radius:10px; font-size:13px;">
        <?php echo e(session('error')); ?>

    </div>
    <?php endif; ?>

    <?php if(auth()->user()->isChatDenied()): ?>
    <div style="margin-bottom:1.25rem; padding:10px 14px; background:#fef2f2; border:0.5px solid #fecaca; color:#b91c1c; border-radius:10px; font-size:13px; display:flex; align-items:center; gap:8px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Your chat access has been suspended by an administrator.
    </div>
    <?php endif; ?>

    <?php
        // Sort each group: users with messages first (by recency), then no-message users
        $sortFn = function($a, $b) {
            $aTime = optional($a->latest_message)->created_at;
            $bTime = optional($b->latest_message)->created_at;
            if ($aTime && $bTime) return $bTime <=> $aTime;
            if ($aTime) return -1;
            if ($bTime) return 1;
            return 0;
        };

        $admins  = $users->filter(fn($u) => in_array($u->role, ['admin', 'superadmin']))->sort($sortFn)->values();
        $members = $users->filter(fn($u) => $u->role === 'user')->sort($sortFn)->values();
    ?>

    
    <?php if($admins->isNotEmpty()): ?>
    <p style="font-size:10.5px; font-weight:600; color:#bbb; text-transform:uppercase; letter-spacing:.07em; margin:0 0 7px 2px;">Admins</p>
    <div style="background:#fff; border:0.5px solid #e8e8e8; border-radius:14px; overflow:hidden; margin-bottom:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,.04);" id="admins-list">
        <?php $__currentLoopData = $admins; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $unread      = $user->unread_count ?? 0;
            $hasUnread   = $unread > 0;
            $isSuperAdmin= $user->role === 'superadmin';
            $avatarBg    = $isSuperAdmin ? '#EEEDFE' : '#E6F1FB';
            $avatarColor = $isSuperAdmin ? '#3C3489' : '#0C447C';
            $lastMsg     = $user->latest_message;
        ?>
        <a href="<?php echo e(route('chat.show', $user)); ?>"
           data-user-id="<?php echo e($user->id); ?>"
           style="display:flex; align-items:center; justify-content:space-between; padding:11px 15px; border-bottom:0.5px solid #f2f2f2; text-decoration:none; background:<?php echo e($hasUnread ? '#fafbff' : 'transparent'); ?>; transition:background .12s; <?php echo e($loop->last ? 'border-bottom:none;' : ''); ?>"
           onmouseover="this.style.background='#f7f7f9'" onmouseout="this.style.background='<?php echo e($hasUnread ? '#fafbff' : 'transparent'); ?>'">
            <div style="display:flex; align-items:center; gap:11px; min-width:0;">
                <div style="width:37px; height:37px; border-radius:50%; background:<?php echo e($avatarBg); ?>; color:<?php echo e($avatarColor); ?>; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:600; flex-shrink:0;">
                    <?php echo e(strtoupper(substr($user->name, 0, 1))); ?>

                </div>
                <div style="min-width:0;">
                    <div style="display:flex; align-items:center; gap:5px; margin-bottom:2px;">
                        <p class="user-name" style="font-size:13px; font-weight:<?php echo e($hasUnread ? '600' : '500'); ?>; color:#111; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo e($user->name); ?></p>
                        <span style="font-size:10px; font-weight:500; padding:1px 6px; border-radius:99px; background:<?php echo e($avatarBg); ?>; color:<?php echo e($avatarColor); ?>; flex-shrink:0;"><?php echo e($isSuperAdmin ? 'Superadmin' : 'Admin'); ?></span>
                    </div>
                    <p class="user-preview" style="font-size:12px; color:<?php echo e($hasUnread ? '#444' : '#bbb'); ?>; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:310px; font-weight:<?php echo e($hasUnread ? '500' : '400'); ?>;">
                        <?php echo e($lastMsg ? mb_strimwidth($lastMsg->body, 0, 80, '…') : 'No messages yet'); ?>

                    </p>
                </div>
            </div>
            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:5px; flex-shrink:0; margin-left:10px;">
                <span class="user-time" style="font-size:11px; color:#ccc; white-space:nowrap;"><?php echo e($lastMsg ? $lastMsg->created_at->diffForHumans(null, true, true) : ''); ?></span>
                <?php if($hasUnread): ?>
                <span class="unread-badge" style="font-size:11px; font-weight:600; background:#1d4ed8; color:#fff; border-radius:99px; padding:1px 7px; min-width:20px; text-align:center;"><?php echo e($unread); ?></span>
                <?php else: ?>
                <span class="unread-badge" style="display:none;"></span>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>

    
    <?php if($members->isNotEmpty()): ?>
    <p style="font-size:10.5px; font-weight:600; color:#bbb; text-transform:uppercase; letter-spacing:.07em; margin:0 0 7px 2px;">Users</p>
    <div style="background:#fff; border:0.5px solid #e8e8e8; border-radius:14px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.04);" id="users-list">
        <?php $__currentLoopData = $members; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $unread    = $user->unread_count ?? 0;
            $hasUnread = $unread > 0;
            $lastMsg   = $user->latest_message;
        ?>
        <a href="<?php echo e(route('chat.show', $user)); ?>"
           data-user-id="<?php echo e($user->id); ?>"
           style="display:flex; align-items:center; justify-content:space-between; padding:11px 15px; border-bottom:0.5px solid #f2f2f2; text-decoration:none; background:<?php echo e($hasUnread ? '#fafbff' : 'transparent'); ?>; transition:background .12s; <?php echo e($loop->last ? 'border-bottom:none;' : ''); ?>"
           onmouseover="this.style.background='#f7f7f9'" onmouseout="this.style.background='<?php echo e($hasUnread ? '#fafbff' : 'transparent'); ?>'">
            <div style="display:flex; align-items:center; gap:11px; min-width:0;">
                <div style="position:relative; flex-shrink:0;">
                    <div style="width:37px; height:37px; border-radius:50%; background:#f3f3f3; color:#666; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:600; border:0.5px solid #eaeaea;">
                        <?php echo e(strtoupper(substr($user->name, 0, 1))); ?>

                    </div>
                    <span class="avatar-dot" style="position:absolute; top:0; right:0; width:8px; height:8px; background:#1d4ed8; border-radius:50%; border:2px solid #fff; <?php echo e($hasUnread ? '' : 'display:none;'); ?>"></span>
                </div>
                <div style="min-width:0;">
                    <p class="user-name" style="font-size:13px; font-weight:<?php echo e($hasUnread ? '600' : '500'); ?>; color:#111; margin:0 0 2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo e($user->name); ?></p>
                    <p class="user-preview" style="font-size:12px; color:<?php echo e($hasUnread ? '#444' : '#bbb'); ?>; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:320px; font-weight:<?php echo e($hasUnread ? '500' : '400'); ?>;">
                        <?php echo e($lastMsg ? mb_strimwidth($lastMsg->body, 0, 80, '…') : 'No messages yet'); ?>

                    </p>
                </div>
            </div>
            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:5px; flex-shrink:0; margin-left:10px;">
                <span class="user-time" style="font-size:11px; color:#ccc; white-space:nowrap;"><?php echo e($lastMsg ? $lastMsg->created_at->diffForHumans(null, true, true) : ''); ?></span>
                <?php if($hasUnread): ?>
                <span class="unread-badge" style="font-size:11px; font-weight:600; background:#1d4ed8; color:#fff; border-radius:99px; padding:1px 7px; min-width:20px; text-align:center;"><?php echo e($unread); ?></span>
                <?php else: ?>
                <span class="unread-badge" style="display:none;"></span>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>

    <?php if($users->isEmpty()): ?>
    <div style="padding:4rem 1rem; text-align:center;">
        <div style="width:40px; height:40px; border-radius:50%; background:#f3f3f3; margin:0 auto 12px; display:flex; align-items:center; justify-content:center;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <p style="font-size:13px; color:#bbb; margin:0;">No other users registered yet.</p>
    </div>
    <?php endif; ?>

</div>

<script>
(function () {
    // Re-sort a list container by data-sort-ts attribute (descending = newest first)
    function resortList(listId) {
        var list = document.getElementById(listId);
        if (!list) return;
        var rows = Array.from(list.querySelectorAll('a[data-user-id]'));
        rows.sort(function (a, b) {
            var at = parseInt(a.dataset.sortTs || '0', 10);
            var bt = parseInt(b.dataset.sortTs || '0', 10);
            return bt - at; // descending
        });
        rows.forEach(function (row) { list.appendChild(row); });

        // Re-apply border-bottom: remove from all, add back to all except last visible
        var visible = list.querySelectorAll('a[data-user-id]');
        visible.forEach(function (r, i) {
            r.style.borderBottom = (i < visible.length - 1) ? '0.5px solid #f2f2f2' : 'none';
        });
    }

    function updateList(data) {
        data.forEach(function (u) {
            var row = document.querySelector('a[data-user-id="' + u.id + '"]');
            if (!row) return;

            var badge   = row.querySelector('.unread-badge');
            var dot     = row.querySelector('.avatar-dot');
            var preview = row.querySelector('.user-preview');
            var timeEl  = row.querySelector('.user-time');
            var nameEl  = row.querySelector('.user-name');
            var hasUnread = u.unread_count > 0;

            // Store sort timestamp so we can re-order
            if (u.sort_ts) row.dataset.sortTs = u.sort_ts;

            // Unread badge
            if (badge) {
                badge.textContent = u.unread_count;
                badge.style.display = hasUnread ? 'inline-block' : 'none';
            }
            // Avatar dot
            if (dot) dot.style.display = hasUnread ? 'block' : 'none';

            // Name weight
            if (nameEl) nameEl.style.fontWeight = hasUnread ? '600' : '500';

            // Preview
            if (preview && u.preview !== undefined) {
                preview.textContent = u.preview || 'No messages yet';
                preview.style.color  = hasUnread ? '#444' : '#bbb';
                preview.style.fontWeight = hasUnread ? '500' : '400';
            }

            // Time
            if (timeEl && u.time_ago) timeEl.textContent = u.time_ago;

            // Unread row tint
            row.dataset.unread = hasUnread ? '1' : '0';
            if (!row.matches(':hover')) {
                row.style.background = hasUnread ? '#fafbff' : 'transparent';
            }
        });

        // Re-sort both lists after updating
        resortList('admins-list');
        resortList('users-list');
    }

    function poll() {
        fetch('<?php echo e(route('chat.inbox')); ?>', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(updateList)
        .catch(function () {});
    }

    // Stamp initial sort order from rendered timestamp
    document.querySelectorAll('a[data-user-id]').forEach(function (row) {
        var timeEl = row.querySelector('.user-time');
        // We'll rely on the server to send sort_ts in the poll response.
        // Initialise to 0 so rows without messages sort to bottom.
        row.dataset.sortTs = row.dataset.sortTs || '0';
    });

    setInterval(poll, 5000);
})();
</script>

</div>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Licensed User\Downloads\OJT---CHAT-SYSTEM-chat-approval\OJT---CHAT-SYSTEM-chat-approval\resources\views/chat/index.blade.php ENDPATH**/ ?>