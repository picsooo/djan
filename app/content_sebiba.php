<?php
/* First content of the Sebiba page (editable afterwards in the admin, Textes des pages > Sebiba). */
$i = fn(string $n) => 'assets/img/w-' . $n . '.jpg';
return [
    'hero_img' => $i('sb-hero'),
    'unesco_url' => 'https://ich.unesco.org/fr/RL/rituel-et-ceremonies-de-la-sebeiba-dans-l-oasis-de-djanet-algerie-00665',
    'fr' => [
        'h' => 'La Sebiba de Djanet',
        'lead' => 'Chaque année, pour l’Achoura, les habitants de Djanet dansent et chantent la Sebiba. Nous vous faisons vivre la fête et ses préparatifs.',
        'intro_h' => 'Une fête inscrite à l’UNESCO',
        'ps' => [
            'La Sebiba est la grande fête de l’oasis de Djanet. Elle est célébrée pendant dix jours, au premier mois du calendrier lunaire musulman, et se termine le jour de l’Achoura.',
            'Deux quartiers de l’oasis s’y retrouvent : El Mihan et Azellouaz. Les hommes dansent en tenue de guerrier, sabre à la main, pendant que les femmes chantent au rythme des tambourins.',
            'Le rituel et les cérémonies de la Sebiba sont inscrits depuis 2014 sur la liste du patrimoine culturel immatériel de l’humanité de l’UNESCO.',
        ],
        'src' => 'Source : UNESCO, patrimoine culturel immatériel',
        'steps_h' => 'Les préparatifs, étape par étape',
        'steps_p' => 'Avec Mina, vous suivez les jours qui précèdent la fête, au milieu des familles de Djanet.',
        'steps' => [
            ['t' => 'Timoulaouine, les répétitions', 'p' => 'Les soirs qui précèdent la fête, danseurs et chanteuses se retrouvent pour répéter. C’est pendant ces soirées que l’on désigne ceux qui représenteront leur quartier.', 'img' => '', 'vid' => 'sb1'],
            ['t' => 'Le henné', 'p' => 'Les femmes préparent le henné et s’en décorent les mains et les pieds. Un moment calme, entre femmes, auquel nos voyageuses sont invitées.', 'img' => $i('sb-henna1'), 'vid' => 'sb2'],
            ['t' => 'Le plat traditionnel', 'p' => 'On cuisine la leftat : une pâte très fine, émiettée et arrosée d’une sauce rouge à la viande. Le plat se partage ensuite, assis autour du même plat.', 'img' => $i('sb-plat1'), 'vid' => 'sb3'],
            ['t' => 'La tenue des femmes', 'p' => 'Voiles, tissus teints et lourds bijoux d’argent : les femmes de Djanet vous habillent comme elles le font pour la fête.', 'img' => $i('sb-tenue1'), 'vid' => 'sb4'],
        ],
        'fest_h' => 'Le jour de la fête',
        'fest_p' => 'Le dernier jour, tout Djanet se rassemble pour voir danser les deux quartiers.',
        'items' => [
            ['t' => 'La danse des hommes', 'p' => 'Les danseurs avancent en cercle, sabre dans une main et foulard dans l’autre, en faisant cliqueter les lames.', 'img' => $i('sb-f3')],
            ['t' => 'La tenue des hommes', 'p' => 'Coiffe haute, voile, ceinture brodée et tissus croisés sur la poitrine.', 'img' => $i('sb-f4')],
            ['t' => 'Le chant des femmes', 'p' => 'Les femmes chantent et frappent le tambourin : ce sont elles qui donnent le rythme aux danseurs.', 'img' => $i('sb-f2')],
            ['t' => 'La tenue des femmes', 'p' => 'Bijoux d’argent, tresses et voiles teints à l’indigo, portés pour le grand jour.', 'img' => $i('sb-f1')],
        ],
        'vid_h' => 'La Sebiba en vidéo',
        'vid_p' => 'Quelques instants filmés pendant la fête.',
        'vid_cap' => 'La danse des hommes, avec le son',
        'cta_btn' => 'Venir pour la Sebiba',
    ],
    'en' => [
        'h' => 'The Sebiba of Djanet',
        'lead' => 'Every year, for Ashura, the people of Djanet dance and sing the Sebiba. We take you to the celebration and its preparations.',
        'intro_h' => 'A UNESCO-listed celebration',
        'ps' => [
            'The Sebiba is the great celebration of the Djanet oasis. It lasts ten days, in the first month of the Muslim lunar calendar, and ends on the day of Ashura.',
            'Two districts of the oasis meet: El Mihan and Azellouaz. The men dance in warrior dress, sword in hand, while the women sing to the rhythm of tambourines.',
            'The ritual and ceremonies of the Sebiba have been on UNESCO’s list of the intangible cultural heritage of humanity since 2014.',
        ],
        'src' => 'Source: UNESCO, intangible cultural heritage',
        'steps_h' => 'The preparations, step by step',
        'steps_p' => 'With Mina, you follow the days before the celebration, among the families of Djanet.',
        'steps' => [
            ['t' => 'Timoulaouine, the rehearsals', 'p' => 'On the evenings before the celebration, dancers and singers meet to rehearse. These evenings decide who will represent each district.'],
            ['t' => 'Henna', 'p' => 'The women prepare henna and decorate their hands and feet. A quiet moment among women, to which our female travellers are invited.'],
            ['t' => 'The traditional dish', 'p' => 'Leftat is cooked: a very thin dough, crumbled and covered with a red meat sauce. It is then shared, everyone sitting around the same dish.'],
            ['t' => 'The women’s dress', 'p' => 'Veils, dyed fabrics and heavy silver jewellery: the women of Djanet dress you as they do for the celebration.'],
        ],
        'fest_h' => 'The day of the celebration',
        'fest_p' => 'On the last day, all of Djanet gathers to watch the two districts dance.',
        'items' => [
            ['t' => 'The men’s dance', 'p' => 'The dancers move in a circle, a sword in one hand and a scarf in the other, clinking the blades.'],
            ['t' => 'The men’s dress', 'p' => 'Tall headdress, veil, embroidered belt and fabrics crossed over the chest.'],
            ['t' => 'The women’s singing', 'p' => 'The women sing and beat the tambourine: they set the rhythm for the dancers.'],
            ['t' => 'The women’s dress', 'p' => 'Silver jewellery, braids and indigo-dyed veils, worn for the big day.'],
        ],
        'vid_h' => 'The Sebiba on video',
        'vid_p' => 'A few moments filmed during the celebration.',
        'vid_cap' => 'The men’s dance, with sound',
        'cta_btn' => 'Come for the Sebiba',
    ],
];
