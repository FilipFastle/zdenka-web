<button class="zc-scroll-top" id="zcScrollTop" aria-label="Späť hore">
    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
</button>

<footer class="zc-footer">
    <div class="zc-footer-grid">

        <!-- Brand -->
        <div>
            <a href="<?php echo home_url(); ?>" class="zc-footer-logo" style="display:flex;align-items:center;gap:10px;text-decoration:none">
                <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/zc-logo.svg"
                     style="width:36px;height:36px;object-fit:contain;filter:brightness(0);flex-shrink:0" alt="ZC">
                <span>
                    <?php echo esc_html(zc_agent('name', 'Mgr. Zdenka Cibuľová')); ?>
                    <small><?php echo esc_html(zc_agent('title','Realitná maklérka')); ?></small>
                </span>
            </a>
            <p class="zc-footer-desc">Profesionálna realitná maklérka v Banskej Bystrici a okolí. Predaj, prenájom aj bezplatný odhad nehnuteľnosti.</p>

            <!-- Contact icons -->
            <div style="display:flex;gap:10px;margin-top:20px">
                <?php
                $phone = zc_agent('phone','+421 907 579 742');
                $wa    = preg_replace('/[^0-9]/', '', zc_agent('wa','421907579742'));
                $email = zc_agent('email', get_option('admin_email'));
                foreach ([
                    ['tel:'.preg_replace('/[^0-9+]/','',$phone), '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>'],
                    ['https://wa.me/'.$wa, '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>'],
                    ['mailto:'.esc_attr($email), '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg>'],
                ] as [$href, $icon]):
                ?>
                <a href="<?php echo $href; ?>" style="width:36px;height:36px;background:rgba(184,164,122,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#6B6053;transition:all .2s" onmouseover="this.style.background='rgba(184,164,122,.35)';this.style.color='#1C1A18'" onmouseout="this.style.background='rgba(184,164,122,.15)';this.style.color='#6B6053'"><?php echo $icon; ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Menu -->
        <div>
            <h4>Navigácia</h4>
            <ul>
                <?php foreach([
                    ['/','Domov'],['/o-mne/','O mne'],
                    ['/ako-pracujem/','Ako pracujem'],['/ponuky/','Ponuky'],
                    ['/odhad/','Odhad ZDARMA'],['/kontakt/','Kontakt'],
                ] as [$path,$label]): ?>
                <li><a href="<?php echo home_url($path); ?>"><?php echo $label; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Kontakt -->
        <div>
            <h4>Kontakt</h4>
            <ul>
                <li>
                    <a href="tel:<?php echo preg_replace('/[^0-9+]/','',$phone); ?>">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" style="flex-shrink:0"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>
                        <?php echo esc_html($phone); ?>
                    </a>
                </li>
                <li>
                    <a href="mailto:<?php echo esc_attr($email); ?>">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg>
                        <?php echo esc_html($email); ?>
                    </a>
                </li>
                <li style="margin-top:8px">
                    <a href="<?php echo home_url('/odhad/'); ?>" style="color:var(--accent) !important;font-weight:600">
                        Odhad nehnuteľnosti →
                    </a>
                </li>
            </ul>
        </div>

    </div>
    <div class="zc-footer-bottom">
        <span>© <?php echo date('Y'); ?> <?php echo esc_html(zc_agent('name', 'Mgr. Zdenka Cibuľová')); ?>. Všetky práva vyhradené.</span>
        <span style="display:inline-flex;gap:14px;align-items:center;flex-wrap:wrap">
            <a href="<?php echo home_url('/ochrana-osobnych-udajov/'); ?>">Ochrana osobných údajov</a>
            <span>Realitná maklérka · Banská Bystrica · Zvolen</span>
        </span>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
