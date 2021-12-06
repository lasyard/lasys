<span class="nobr">
    <em><?php echo date('Y.m.d h:i:s', $time); ?></em> by <?php echo $uname; ?>
</span>
<?php if ($edit) { ?>
    <span class="nobr">
        <i id='btn-delete' class="bi bi-x-square" style="float:right"></i>
    </span>
    <script>
        byId('btn-delete').addEventListener('click', () => {
            const r = confirm('Are you sure to delete "<?php echo $name; ?>"?');
            if (r) {
                Ajax.delete(function(res, type) {
                    byId('main').innerHTML = res;
                });
            }
        });
    </script>
<?php } ?>
