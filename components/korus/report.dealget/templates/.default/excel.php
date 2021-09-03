<div>
    <table cellspacing="0" class="reports-list-table">
        <thead>
        <tr>
            <?php foreach ($arResult['TABLE'] as $id => $data) { ?>
                <?php foreach ($data as $key => $val) { ?>
                    <td> <?= $val['head'] ?></td>
                <?php } ?>
                <?php break ?>
            <?php } ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($arResult['TABLE'] as $id => $data) { ?>
            <tr>
                <?php foreach ($data as $key => $val) { ?>
                    <?php
                    if ($key == 'TITLE') {
                        ?><td> <a href="/crm/deal/details/<?= $id ?>/"><?= $val['data'] ?></a></td> <?php
                        continue;
                    }
                    ?>
                    <td> <?= $val['data'] ?></td>
                <?php } ?>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    <h3>Среднее время нахождения в стадии</h3>
    <table cellspacing="0" class="reports-list-table">
        <thead>
        <tr>
            <?php foreach ($arResult['TIME_STAGES'] as $stage => $data) { ?>
                <th class="reports-first-column reports-head-cell-top" style="white-space: nowrap; background-color: #F0F0F0;"> <?= $data['title'] ?></th>
            <?php } ?>
        </tr>
        </thead>
        <tbody>
        <tr>
            <?php foreach ($arResult['TIME_STAGES'] as $stage => $data) { ?>
                <td style="white-space: nowrap;"> <?= $data['middle'] ?></td>
            <?php } ?>
        </tr>
        </tbody>
    </table>
</div>