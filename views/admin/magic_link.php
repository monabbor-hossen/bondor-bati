<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Generate Magic Links | Bondor Bati</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
</head>

<body class="bg-slate-50 font-[Inter] min-h-screen">

    <div class="max-w-3xl mx-auto p-6 lg:p-8 mt-10">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900">Staff Access Control</h1>
                <p class="text-slate-500">Generate secure, one-time login links for your team.</p>
            </div>
            <a href="/bondor-bati/admin/dashboard"
                class="px-4 py-2 bg-white border border-slate-200 rounded-lg shadow-sm hover:bg-slate-50 font-semibold text-slate-700">
                &larr; Back to Dashboard
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8">

            <?php if (isset($magic_result)): ?>
                <div
                    class="mb-6 p-4 rounded-xl <?= $magic_result['success'] ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-red-50 border border-red-200 text-red-800' ?>">
                    <p class="font-bold">
                        <?= htmlspecialchars($magic_result['message']) ?>
                    </p>

                    <?php if ($magic_result['success'] && isset($magic_result['link'])): ?>
                        <div class="mt-4">
                            <label class="text-xs font-semibold uppercase text-emerald-600 tracking-wider">Share this link via
                                WhatsApp:</label>
                            <div class="flex mt-1">
                                <input type="text" readonly value="<?= htmlspecialchars($magic_result['link']) ?>"
                                    class="w-full bg-white border border-emerald-300 rounded-l-lg px-3 py-2 text-sm focus:outline-none text-slate-600"
                                    id="magicLinkInput">
                                <button
                                    onclick="navigator.clipboard.writeText(document.getElementById('magicLinkInput').value); alert('Copied!');"
                                    class="bg-emerald-600 text-white px-4 py-2 rounded-r-lg font-semibold hover:bg-emerald-700">
                                    Copy
                                </button>
                            </div>
                            <p class="text-xs mt-2 text-emerald-600 italic">* This link is single-use and will expire once
                                clicked.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/bondor-bati/admin/magic-link">
                <label for="staff_id" class="block text-sm font-semibold text-slate-700 mb-2">Select Staff
                    Member</label>
                <select name="staff_id" id="staff_id" required
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none mb-6 font-medium text-slate-700">
                    <option value="" disabled selected>-- Choose a staff member --</option>
                    <?php foreach ($staff_list as $staff): ?>
                        <option value="<?= $staff['id'] ?>">
                            <?= htmlspecialchars($staff['name']) ?> (@
                            <?= htmlspecialchars($staff['username']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit"
                    class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl shadow-lg transition-all">
                    Generate Magic Link
                </button>
            </form>

        </div>
    </div>
</body>

</html>