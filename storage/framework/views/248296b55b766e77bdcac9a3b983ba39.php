<?php $__env->startSection('title', 'Chat Requests'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-6xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Chat Requests</h1>

    <?php if($conversations->isEmpty()): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
            <p class="text-gray-500">No pending chat requests.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Sender</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Receiver</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">First Message</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $conversations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conversation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="border-b border-gray-100">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-semibold text-xs">
                                    <?php echo e(mb_strtoupper(mb_substr($conversation['user']->name, 0, 1))); ?>

                                </div>
                                <span class="font-medium text-gray-800"><?php echo e($conversation['user']->name); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-700 font-semibold text-xs">
                                    <?php echo e(mb_strtoupper(mb_substr($conversation['partner']->name, 0, 1))); ?>

                                </div>
                                <span class="font-medium text-gray-800"><?php echo e($conversation['partner']->name); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-600 max-w-xs truncate">
                            <?php echo e($conversation['first_message'] ?? 'No message'); ?>

                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <?php echo e(\Carbon\Carbon::parse($conversation['created_at'])->format('M d, Y g:i A')); ?>

                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <button 
                                    onclick="updateConversation(<?php echo e($conversation['id']); ?>, 'accept')"
                                    class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-medium transition-colors">
                                    Accept
                                </button>
                                <button 
                                    onclick="updateConversation(<?php echo e($conversation['id']); ?>, 'reject')"
                                    class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-medium transition-colors">
                                    Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function updateConversation(conversationId, action) {


    fetch(`/conversations/${conversationId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ action: action })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            if (action === 'accept' && data.redirect_url) {
                window.location.href = data.redirect_url;
            } else {
                location.reload();
            }
        } else if (data.error) {
            alert(data.error);
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
    });
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Licensed User\Downloads\OJT---CHAT-SYSTEM-chat-approval\OJT---CHAT-SYSTEM-chat-approval\resources\views/conversations/index.blade.php ENDPATH**/ ?>