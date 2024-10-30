<div class="wrap">
<h1>iKorektor Plugin</h1>
Do poprawnego działania pluginu wymagane jest <a href="https://ikorektor.pl/kontakt" target="_blank" rel="noopener">zgłoszenie</a> nazwy strony (domeny) do aktywacji po stronie aplikacji.<br>
Więcej informacji o wtyczce i działaniu poszczególnych opcji znajdziesz na stronie <a href="https://ikorektor.pl/pluginy" target="_blank" rel="noopener">https://ikorektor.pl/pluginy</a>.
<?php settings_errors(); ?>
<form method="POST" action="options.php">
<h2>Ustawienia</h2>
<table class="form-table">
<th></th>
<td><p class="ik-opt"><b>Witryna</b></p><p class="ik-opt"><b>Panel Admina</b></p></td>
</table>
<?php
    settings_fields('ikorektor_options_group');
    do_settings_sections('ikorektor');
    submit_button();
?>
</form>
</div>