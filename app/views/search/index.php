<div class="page-head">
    <div>
        <h1 class="page-head__title">البحث الفني</h1>
        <p class="muted">ابحث في الحالات الفنية والمعلومات والأعطال والحلول.</p>
    </div>
</div>

<div class="container technical-search">
    <form class="card technical-search__form" method="get" action="<?= $e($baseUrl) ?>/search">
        <label class="field">
            <span class="field__label">ابحث عن جهاز أو عطل أو مكون أو هاشتاج</span>
            <input class="field__input" type="search" name="q" value="<?= $e($query) ?>"
                maxlength="120" placeholder="مثال: Samsung A52 أو #شورت أو دائرة الشحن" autofocus>
        </label>
        <div class="technical-search__filters">
            <label class="field">
                <span class="field__label">نوع المحتوى</span>
                <select class="field__input" name="type">
                    <option value="">كل المحتوى</option>
                    <option value="help_request" <?= $type === 'help_request' ? 'selected' : '' ?>>طلبات المساعدة</option>
                    <option value="technical_post" <?= $type === 'technical_post' ? 'selected' : '' ?>>المعلومات الفنية</option>
                    <option value="discussion" <?= $type === 'discussion' ? 'selected' : '' ?>>النقاشات</option>
                </select>
            </label>
            <button class="btn btn--primary" type="submit">بحث</button>
        </div>
    </form>

    <?php if ($query !== '') : ?>
        <div class="technical-search__heading">
            <h2>نتائج البحث</h2>
            <span class="muted"><?= count($results) ?> نتيجة</span>
        </div>
        <?php if ($searchRateLimited) : ?>
            <div class="card empty-state"><p class="empty-state__text">طلبات البحث كثيرة في وقت قصير. انتظر دقيقة ثم حاول مرة أخرى.</p></div>
        <?php elseif (!$results) : ?>
            <div class="card empty-state"><p class="empty-state__text">مفيش نتائج مطابقة. جرّب كلمة مختلفة أو هاشتاج.</p></div>
        <?php else : ?>
            <div class="technical-search__results">
                <?php foreach ($results as $item) : ?>
                    <article class="card technical-search__result">
                        <div class="technical-search__meta">
                            <span class="tag"><?= $e($item['type_label']) ?></span>
                            <?php if ($item['status'] === 'resolved') : ?><span class="tag technical-search__solved">تم الحل</span><?php endif; ?>
                            <?php if ($item['device_label'] !== '') : ?><span class="tag tech"><?= $e($item['device_label']) ?></span><?php endif; ?>
                        </div>
                        <h3><a href="<?= $e($baseUrl) ?>/issues/<?= (int) $item['id'] ?>"><?= $e($item['title']) ?></a></h3>
                        <?php if ($item['excerpt'] !== '') : ?><p class="muted"><?= $e($item['excerpt']) ?></p><?php endif; ?>
                        <?php if ($item['tags']) : ?>
                            <div class="technical-search__tags">
                                <?php foreach ($item['tags'] as $tag) : ?><a class="tag" href="<?= $e($baseUrl) ?>/search?q=<?= rawurlencode('#' . $tag) ?>">#<?= $e($tag) ?></a><?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="technical-search__foot"><span><?= $e($item['author']) ?></span><span><?= (int) $item['reply_count'] ?> رد</span><time><?= $e($item['created_at']) ?></time></div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <div class="card technical-search__hint">
            <strong>ابحث داخل المعرفة الفنية للمجتمع</strong>
            <p class="muted">البحث يشمل العناوين والتفاصيل والأجهزة والموديلات والهاشتاجات والردود وتحديثات التشخيص ونتائج الإصلاح. لا يعرض حسابات الفنيين كنتاج بحث.</p>
        </div>
    <?php endif; ?>
</div>
