<form method="post" action="index.php" class="content-pad" style="padding-top:32px;display:flex;flex-direction:column;gap:16px;min-height:100%;">
    <input type="hidden" name="action" value="kirim_verifikasi">

    <div class="card">
        <div class="person-row">
            <div class="avatar-circle">
                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13h13V6H3v7zm0 0l3 5h9l3-5m-6-7v7m-9 0h18"/></svg>
            </div>
            <div>
                <p class="person-name">B 9170 SEJ</p>
                <p class="person-sub">16 KL</p>
            </div>
        </div>
        <p class="question">Apakah mobil tangki sesuai?</p>
        <div class="row">
            <label class="btn-choice red" style="cursor:pointer;">
                <input type="radio" name="mt_ok" value="tidak" style="display:none;"> Tidak sesuai
            </label>
            <label class="btn-choice blue" style="cursor:pointer;">
                <input type="radio" name="mt_ok" value="ya" checked style="display:none;"> Ya, sesuai
            </label>
        </div>
    </div>

    <div class="card">
        <div class="person-row">
            <div class="avatar-circle">
                <img src="https://ui-avatars.com/api/?name=Farhan&background=0D8ABC&color=fff" alt="Avatar">
            </div>
            <div>
                <p class="person-name small">MOHAMMAD FARHAN</p>
                <p class="person-sub">AMT 1</p>
            </div>
        </div>
        <p class="question">Apakah AMT 1 sesuai?</p>
        <div class="row">
            <label class="btn-choice red" style="cursor:pointer;">
                <input type="radio" name="amt_ok" value="tidak" style="display:none;"> Tidak sesuai
            </label>
            <label class="btn-choice blue" style="cursor:pointer;">
                <input type="radio" name="amt_ok" value="ya" checked style="display:none;"> Ya, sesuai
            </label>
        </div>
    </div>

    <button type="submit" class="btn-primary" style="margin-top:auto;">Kirim Verifikasi MT dan AMT</button>
</form>
