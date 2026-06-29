

<?php $__env->startSection('title', 'Messages'); ?>

<?php $__env->startSection('content'); ?>

<div class="max-w-5xl mx-auto py-8 px-4">

<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">
            Messages
        </h1>
        <p class="text-gray-500">
            Select a user to start a conversation
        </p>
    </div>

    <div class="text-right">
        <p class="font-semibold text-gray-800">
            <?php echo e(auth()->user()->name); ?>

        </p>
        <p class="text-sm text-gray-500">
            <?php echo e(auth()->user()->email); ?>

        </p>
    </div>
</div>

<?php if(session('error')): ?>
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-xl">
        <?php echo e(session('error')); ?>

    </div>
<?php endif; ?>

<?php if(auth()->user()->isChatDenied()): ?>
    <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-xl">
        Your chat access has been suspended by an administrator.
    </div>
<?php endif; ?>

<div class="bg-white shadow rounded-2xl overflow-hidden">

    <div class="px-6 py-4 border-b bg-gray-50">
        <h2 class="font-semibold text-gray-700">
            Registered Users
        </h2>
    </div>

    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

        <a href="<?php echo e(route('chat.show', $user)); ?>"
           class="flex items-center justify-between px-6 py-4 border-b hover:bg-gray-50 transition">

            <div class="flex items-center gap-4">

                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
                    <?php echo e(strtoupper(substr($user->name, 0, 1))); ?>

                </div>

                <div>
                    <p class="font-semibold text-gray-800">
                        <?php echo e($user->name); ?>

                    </p>

                    <p class="text-sm text-gray-500">
                        <?php echo e($user->email); ?>

                    </p>
                </div>

            </div>

            <div>
                <span class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg">
                    Chat
                </span>
            </div>

        </a>

    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

        <div class="p-10 text-center text-gray-500">
            No other registered users found.
        </div>

    <?php endif; ?>

</div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Rivera - OJT\OJT---CHAT-SYSTEM\resources\views/chat/index.blade.php ENDPATH**/ ?>