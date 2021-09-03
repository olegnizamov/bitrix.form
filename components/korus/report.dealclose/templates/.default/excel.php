<div>
    <table cellspacing="0" class="reports-list-table">
        <thead>
        <tr>
            <?php foreach ($arResult['HEAD'] as $title => $name) { ?>
                <td> <?= $name ?></td>
            <?php } ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($arResult['DEALS'] as $id => $data) { ?>
            <tr>
                <?php foreach ($data as $key => $val) { ?>
                    <?php
                    if ($key == 'TITLE') {
                        ?><td> <a href="/crm/deal/details/<?= $id ?>/"><?= $val ?></a></td> <?php
                        continue;
                    }
                    ?>
                    <td> <?= $val ?></td>
                <?php } ?>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    <h3>Среднее время закрытия обращения</h3>
    <table cellspacing="0" class="reports-list-table">
        <thead>
        <tr>
            <?php foreach ($arResult['TIME_DIRECTION'] as $direction => $data) { ?>
                <th class="reports-first-column reports-head-cell-top" style="white-space: nowrap; background-color: #F0F0F0;"> <?= $direction ?></th>
            <?php } ?>
        </tr>
        </thead>
        <tbody>
        <tr>
            <?php foreach ($arResult['TIME_DIRECTION'] as $direction => $data) { ?>
                <td style="white-space: nowrap;"> <?= $data['middle'] ?></td>
            <?php } ?>
        </tr>
        </tbody>
    </table>
</div>