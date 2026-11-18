<?php
/** Cenas 2D em camadas da Máquina do tempo (Observatório).
 *  SVG leve, sem imagens externas. As camadas só se movem quando o modo interativo está ligado. */
if (!defined('MAT_ROOT')) { exit; }
?>
<div class="mat-tm-scene" aria-hidden="true">
 <div class="mat-tm-scene-pane scene-prehistoria">
  <span class="mat-tm-sky"></span>
  <svg viewBox="0 0 800 360" preserveAspectRatio="xMidYMid slice" focusable="false">
   <g class="layer-far" data-depth=".2">
    <circle cx="640" cy="96" r="46" fill="#f0b978" opacity=".55"/>
    <g fill="#f4e2c4" opacity=".5"><circle cx="92" cy="64" r="1.4"/><circle cx="160" cy="98" r="1"/><circle cx="252" cy="52" r="1.2"/><circle cx="332" cy="120" r="1"/><circle cx="424" cy="72" r="1.3"/></g>
    <path d="M0 260 120 150 220 240 330 120 450 250 570 175 690 255 800 205V360H0Z" fill="#735043" opacity=".55"/>
   </g>
   <g class="layer-mid" data-depth=".55">
    <path d="M0 286c80-18 140 8 220-2 90-12 150 18 245 4 95-14 170 14 335-6v78H0Z" fill="#44302a" opacity=".9"/>
    <g fill="#2f211c">
     <ellipse cx="592" cy="270" rx="54" ry="34"/>
     <circle cx="646" cy="252" r="25"/>
     <path d="M662 266c20 20 15 37 2 47-7 5-13-2-8-7 12-10 5-22-4-30Z"/>
     <path d="M566 300l-8 34h-11l5-34Zm25 0l-3 34h-11l3-34Zm22 0l5 34h-11l-5-34Zm24 0l10 34h-11l-8-34Z"/>
    </g>
    <path d="M654 268c-10 8-20 23-22 34" fill="none" stroke="#e6d6b7" stroke-width="4" stroke-linecap="round" opacity=".8"/>
    <path d="M366 318c-16-28 2-44 10-58 2 18 10 23 17 28 2-15 11-24 22-30-2 29-9 49-49 60Z" fill="#d58a4f"/>
    <path d="M380 320c-6-10 1-17 5-23 1 7 5 9 8 11 0-6 4-10 9-13-1 12-5 19-22 25Z" fill="#f0c173"/>
    <path d="M392 280c-8-14 0-23 8-31-1 10 7 14 5 24 9-5 18-3 24 3-13 4-24 8-37 4Z" fill="#8d7563" opacity=".28"/>
   </g>
   <g class="layer-near" data-depth="1">
    <path d="M0 326c95-16 170 12 270-2 105-15 185 15 290-2 95-15 170 8 240-4v42H0Z" fill="#171210"/>
    <path d="M0 360V268c28-10 55 2 70 24 9 13 12 35 10 68Z" fill="#0d0a09"/>
    <path d="M46 360c-5-31 3-52 15-68 2 21 11 32 9 51" fill="none" stroke="#241a15" stroke-width="3" opacity=".65"/>
    <path d="M160 350l-36-48-28 48Zm-80 0 40-66 35 66Z" fill="#0f0c0b"/>
   </g>
  </svg>
 </div>

 <div class="mat-tm-scene-pane scene-antiguidade">
  <span class="mat-tm-sky"></span>
  <svg viewBox="0 0 800 360" preserveAspectRatio="xMidYMid slice" focusable="false">
   <g class="layer-far" data-depth=".2">
    <circle cx="630" cy="92" r="42" fill="#f2c082" opacity=".55"/>
    <g stroke="#f6d9ae" stroke-width="1" fill="none" opacity=".28"><path d="M520 116c6-4 10-4 14 0 4-4 8-4 12 0"/><path d="M556 136c5-3 8-3 11 0 3-3 6-3 9 0"/></g>
    <path d="M58 254 184 104 310 254Z" fill="#7a4c35"/>
    <path d="M184 104 310 254H184Z" fill="#5f3928" opacity=".55"/>
    <path d="M280 260 374 158 468 260Z" fill="#87563d" opacity=".85"/>
    <path d="M374 158 468 260H374Z" fill="#643d2b" opacity=".55"/>
    <path d="M0 268c105-28 220 18 330 0 130-22 260 20 470-2v94H0Z" fill="#8b5b3d" opacity=".45"/>
   </g>
   <g class="layer-mid" data-depth=".55">
    <g fill="#402a20" transform="translate(120 0)">
     <rect x="406" y="244" width="258" height="14"/>
     <rect x="420" y="176" width="11" height="68"/>
     <rect x="452" y="176" width="11" height="68"/>
     <rect x="484" y="176" width="11" height="68"/>
     <rect x="546" y="176" width="11" height="68"/>
     <rect x="578" y="176" width="11" height="68"/>
     <rect x="610" y="176" width="11" height="68"/>
     <path d="M402 176 535 120 668 176Z"/>
     <rect x="422" y="166" width="226" height="10"/>
    </g>
    <path d="M0 296c120-22 200 12 316-2 120-14 220 16 484-6v72H0Z" fill="#5d3b29"/>
    <rect x="518" y="258" width="278" height="7" fill="#4a3024"/>
    <rect x="526" y="265" width="262" height="6" fill="#3d2820"/>
    <path d="M550 274v-98" stroke="#9c6a47" stroke-width="3" opacity=".5"/>
   </g>
   <g class="layer-near" data-depth="1">
    <path d="M0 326c105-18 180 12 285-2 110-15 205 14 300-2 82-13 145 8 215-2v40H0Z" fill="#241813"/>
    <g fill="none" stroke="#c79a6c" stroke-width="1.5" opacity=".22"><path d="M60 338c40-8 80 6 120-2"/><path d="M420 342c50-6 96 8 146 0"/></g>
    <path d="M120 350c22-22 46-26 68-20-20 8-31 15-40 30Z" fill="#17100d"/>
   </g>
  </svg>
 </div>

 <div class="mat-tm-scene-pane scene-medieval">
  <span class="mat-tm-sky"></span>
  <svg viewBox="0 0 800 360" preserveAspectRatio="xMidYMid slice" focusable="false">
   <g class="layer-far" data-depth=".2">
    <circle cx="648" cy="90" r="38" fill="#d9c8a4" opacity=".42"/>
    <g stroke="#cfd8cc" stroke-width="1" fill="none" opacity=".3"><path d="M300 112c6-4 10-4 14 0 4-4 8-4 12 0"/><path d="M342 132c5-3 8-3 11 0 3-3 6-3 9 0"/></g>
    <path d="M0 260 110 200 220 250 340 175 480 255 620 190 800 250V360H0Z" fill="#46534f" opacity=".6"/>
   </g>
   <g class="layer-mid" data-depth=".55">
    <g fill="#303432">
     <rect x="452" y="210" width="198" height="92"/>
     <rect x="428" y="164" width="46" height="138"/>
     <rect x="623" y="148" width="52" height="154"/>
     <rect x="528" y="132" width="56" height="170"/>
     <path d="M428 164h46l-23-35Zm195-16h52l-26-38Zm-95-16h56l-28-42Z"/>
     <path d="M428 164v-10h10v10h9v-10h10v10h7v-10h10v10Zm195-16v-10h10v10h10v-10h10v10h10v-10h12v10Zm-95-16v-10h10v10h10v-10h10v10h10v-10h16v10Z"/>
    </g>
    <g fill="#d6a86c">
     <rect x="444" y="188" width="9" height="18" rx="4"/>
     <rect x="575" y="158" width="10" height="20" rx="5"/>
     <rect x="642" y="176" width="9" height="18" rx="4"/>
     <rect x="494" y="246" width="12" height="22" rx="5"/>
    </g>
    <path d="M451 164v-44" stroke="#262b29" stroke-width="3"/>
    <path d="M451 120h34l-10 9 10 9h-34Z" fill="#a7523f"/>
    <path d="M649 148v-38" stroke="#262b29" stroke-width="3"/>
    <path d="M649 110h30l-9 8 9 8h-30Z" fill="#b08a56"/>
    <path d="M0 300c125-20 210 12 330-4 120-16 220 18 470-4v68H0Z" fill="#3a3e37"/>
    <g fill="#252a27"><path d="M292 300l24-18 24 18Z"/><path d="M340 300l18-14 18 14Z"/><rect x="310" y="276" width="11" height="24"/><rect x="348" y="286" width="8" height="14"/></g>
   </g>
   <g class="layer-near" data-depth="1">
    <g fill="#151b19">
     <path d="M94 350l-30-72-32 72Zm70 0-26-62-28 62Zm-145 0-18-44-20 44Z"/>
     <path d="M685 350l-24-58-26 58Zm62 0-18-42-20 42Z"/>
    </g>
    <path d="M0 332c150-8 260 12 390 0 120-10 240 12 410 0v28H0Z" fill="#111614"/>
   </g>
  </svg>
 </div>

 <div class="mat-tm-scene-pane scene-moderna">
  <span class="mat-tm-sky"></span>
  <svg viewBox="0 0 800 360" preserveAspectRatio="xMidYMid slice" focusable="false">
   <g class="layer-far" data-depth=".2">
    <circle cx="636" cy="96" r="40" fill="#f0c58f" opacity=".55"/>
    <g stroke="#d9c7a4" stroke-width="1" fill="none" opacity=".28"><path d="M180 118c6-4 10-4 14 0 4-4 8-4 12 0"/><path d="M214 136c5-3 8-3 11 0 3-3 6-3 9 0"/></g>
    <path d="M0 246h800v16H0Z" fill="#54686a" opacity=".55"/>
    <path d="M0 262c100-10 170 10 260 0 110-12 180 10 280 0 100-10 170 8 260 0v98H0Z" fill="#3b5a5d" opacity=".5"/>
    <path d="M686 196c-34 0-58 24-58 52h116c0-28-24-52-58-52Z" fill="#3c4a48" opacity=".7"/>
   </g>
   <g class="layer-mid" data-depth=".55">
    <g fill="#3d2d24">
     <path d="M322 252c56 18 140 18 196 0l-20 38c-48 12-108 12-156 0Z"/>
     <rect x="416" y="156" width="5" height="100"/>
     <rect x="446" y="132" width="5" height="124"/>
     <path d="M420 158h-52l52-42Z"/>
     <path d="M451 136h54l-54-48Z"/>
    </g>
    <path d="M418 160 372 158 418 128Z" fill="#ead8b6"/>
    <path d="M449 136 494 132 449 98Z" fill="#e3cda7"/>
    <path d="M449 154 500 152 449 122Z" fill="#f0dfc0" opacity=".9"/>
    <path d="M322 262c58 16 140 16 198 0" fill="none" stroke="#7b5138" stroke-width="5"/>
    <path d="M694 248v-58" stroke="#393b36" stroke-width="5"/>
    <path d="M672 190c10-24 34-24 44 0Z" fill="#5e5f54"/>
    <path d="M451 84h30l-9 7 9 7h-30Z" fill="#a7523f"/>
   </g>
   <g class="layer-near" data-depth="1">
    <path d="M0 310c95-14 170 12 270 0 110-12 190 12 300 0 100-10 170 8 230 0v50H0Z" fill="#172727"/>
    <path d="M0 326c110-10 200 8 300 0 110-8 200 8 310 0 80-6 140 4 190 0v34H0Z" fill="#0f1b1b" opacity=".9"/>
    <g fill="none" stroke="#7fa3a0" stroke-width="2" opacity=".3"><path d="M60 324c34-6 60 6 94 0"/><path d="M240 334c40-6 70 6 110 0"/><path d="M520 330c44-6 78 6 122 0"/></g>
   </g>
  </svg>
 </div>

 <div class="mat-tm-scene-pane scene-industrial">
  <span class="mat-tm-sky"></span>
  <svg viewBox="0 0 800 360" preserveAspectRatio="xMidYMid slice" focusable="false">
   <g class="layer-far" data-depth=".2">
    <circle cx="640" cy="90" r="42" fill="#e7b276" opacity=".38"/>
    <g fill="#4f4840" opacity=".75">
     <rect x="88" y="196" width="92" height="94"/>
     <rect x="108" y="142" width="22" height="58"/>
     <rect x="154" y="164" width="18" height="36"/>
     <rect x="238" y="208" width="120" height="82"/>
     <rect x="258" y="164" width="18" height="48"/>
     <rect x="314" y="180" width="20" height="32"/>
     <rect x="650" y="214" width="104" height="76"/>
     <rect x="682" y="168" width="20" height="50"/>
    </g>
    <g fill="#e0b27a" opacity=".45"><rect x="104" y="214" width="10" height="12"/><rect x="130" y="214" width="10" height="12"/><rect x="256" y="226" width="10" height="12"/><rect x="282" y="226" width="10" height="12"/><rect x="666" y="232" width="10" height="12"/></g>
    <circle cx="118" cy="128" r="17" fill="#827463" opacity=".45"/>
    <circle cx="138" cy="110" r="24" fill="#827463" opacity=".3"/>
    <circle cx="270" cy="150" r="18" fill="#827463" opacity=".4"/>
   </g>
   <g class="layer-mid" data-depth=".55">
    <g fill="#2d2b28">
     <path d="M362 272h270l-16-74H386Z"/>
     <rect x="402" y="178" width="78" height="58"/>
     <rect x="512" y="152" width="24" height="62"/>
     <rect x="574" y="162" width="52" height="52"/>
     <circle cx="398" cy="278" r="20"/>
     <circle cx="444" cy="278" r="20"/>
     <circle cx="568" cy="278" r="20"/>
     <circle cx="616" cy="278" r="20"/>
    </g>
    <g fill="#c18a59" opacity=".75">
     <rect x="418" y="192" width="20" height="18"/>
     <rect x="450" y="192" width="20" height="18"/>
    </g>
    <path d="M512 154v-34" stroke="#2d2b28" stroke-width="7"/>
    <circle cx="512" cy="108" r="18" fill="#8d8170" opacity=".35"/>
    <circle cx="536" cy="88" r="24" fill="#8d8170" opacity=".24"/>
    <circle cx="500" cy="72" r="29" fill="#8d8170" opacity=".16"/>
    <circle cx="628" cy="252" r="5" fill="#f0d09a" opacity=".7"/>
    <path d="M634 252 690 240v20Z" fill="#f0d09a" opacity=".1"/>
    <path d="M0 300c120-14 210 10 330 0 130-10 230 12 470-2v62H0Z" fill="#3d3832"/>
   </g>
   <g class="layer-near" data-depth="1">
    <path d="M0 328h800v32H0Z" fill="#171614"/>
    <path d="M0 312h800" stroke="#655445" stroke-width="4"/>
    <path d="M0 336h800" stroke="#655445" stroke-width="4"/>
    <g stroke="#1b1916" stroke-width="3" opacity=".8">
     <path d="M95 340v20M135 340v20M175 340v20M215 340v20M255 340v20M295 340v20M335 340v20M375 340v20M415 340v20M455 340v20M495 340v20M535 340v20M575 340v20M615 340v20M655 340v20M695 340v20M735 340v20"/>
    </g>
   </g>
  </svg>
 </div>

 <div class="mat-tm-scene-pane scene-digital">
  <span class="mat-tm-sky"></span>
  <svg viewBox="0 0 800 360" preserveAspectRatio="xMidYMid slice" focusable="false">
   <g class="layer-far" data-depth=".2">
    <circle cx="646" cy="102" r="58" fill="#d9d1b8" opacity=".5"/>
    <circle cx="624" cy="88" r="10" fill="#b9b29d" opacity=".35"/>
    <circle cx="664" cy="118" r="7" fill="#b9b29d" opacity=".3"/>
    <g fill="#e9e1c9">
     <circle cx="120" cy="70" r="2"/><circle cx="190" cy="110" r="2"/><circle cx="280" cy="54" r="2"/>
     <circle cx="360" cy="90" r="2"/><circle cx="520" cy="62" r="2"/><circle cx="720" cy="74" r="2"/>
     <circle cx="760" cy="140" r="2"/><circle cx="70" cy="150" r="2"/>
     <circle cx="230" cy="150" r="1.3"/><circle cx="430" cy="48" r="1.2"/><circle cx="600" cy="130" r="1.4"/>
    </g>
    <path d="M612 216c-34 0-62-20-62-46s28-46 62-46 62 20 62 46-28 46-62 46Z" fill="none" stroke="#9fb2b5" stroke-width="1" opacity=".4"/>
    <g fill="#c7d2cd">
     <rect x="682" y="132" width="30" height="15" rx="2" transform="rotate(-18 682 132)"/>
     <path d="M694 146v26" stroke="#c7d2cd" stroke-width="3"/>
     <rect x="684" y="172" width="22" height="8" rx="2" transform="rotate(-18 684 172)"/>
    </g>
   </g>
   <g class="layer-mid" data-depth=".55">
    <g fill="#d7d2c5">
     <path d="M330 286V170c0-34 20-58 42-58s42 24 42 58v116Z"/>
     <path d="M330 220 300 274l30-12Zm84 0 30 54-30-12Z" fill="#a7523f"/>
     <circle cx="372" cy="202" r="13" fill="#3c6770"/>
     <path d="M364 246h16v16h-16Z" fill="#5f5b52"/>
    </g>
    <path d="M353 286c2 30 10 44 19 52 9-8 17-22 19-52Z" fill="#e0a45f"/>
    <g fill="#b9c8c6" opacity=".22"><circle cx="340" cy="324" r="20"/><circle cx="396" cy="332" r="24"/><circle cx="306" cy="334" r="16"/></g>
    <rect x="314" y="334" width="116" height="8" rx="2" fill="#4b5b5b" opacity=".55"/>
    <circle cx="372" cy="202" r="17" fill="none" stroke="#8fa8a8" stroke-width="2" opacity=".45"/>
    <path d="M362 286c1 20 5 31 10 38 5-7 9-18 10-38Z" fill="#f2d39a"/>
    <g fill="#2e3333">
     <rect x="492" y="252" width="120" height="70" rx="8"/>
     <rect x="514" y="272" width="76" height="30" rx="2" fill="#537278"/>
     <rect x="520" y="278" width="44" height="3" fill="#a9c2c0"/>
     <rect x="520" y="286" width="58" height="3" fill="#a9c2c0"/>
     <rect x="520" y="294" width="34" height="3" fill="#a9c2c0"/>
     <rect x="536" y="322" width="34" height="6" rx="2"/>
    </g>
    <path d="M520 340h70" stroke="#6f8684" stroke-width="3" opacity=".5"/>
   </g>
   <g class="layer-near" data-depth="1">
    <path d="M0 330c100-12 180 10 290 0 120-10 210 12 320 0 80-8 140 4 190 0v30H0Z" fill="#0a151b"/>
    <g fill="none" stroke="#4e7d84" stroke-width="1.5" opacity=".45">
     <path d="M0 318h80v-26h60v26h70v-34h64v34h72v-22h80v22h70v-30h64v30h80"/>
     <path d="M0 342h120v-14h90v14h110v-18h100v18h120v-12h90v12h170"/>
    </g>
   </g>
  </svg>
 </div>

 <div class="mat-tm-scene-pane scene-contemporanea">
  <span class="mat-tm-sky"></span>
  <svg viewBox="0 0 800 360" preserveAspectRatio="xMidYMid slice" focusable="false">
   <g class="layer-far" data-depth=".2">
    <circle cx="690" cy="376" r="190" fill="#2e5f63"/>
    <path d="M610 254c34-22 74-10 94 8 24-6 48 4 56 22-44 20-124 22-174 4 8-14 14-24 24-34Z" fill="#5e8b78" opacity=".8"/>
    <g fill="#d8e3d3">
     <circle cx="100" cy="70" r="2"/><circle cx="180" cy="112" r="2"/><circle cx="260" cy="60" r="2"/>
     <circle cx="350" cy="96" r="2"/><circle cx="450" cy="54" r="2"/><circle cx="540" cy="108" r="2"/>
    </g>
    <g fill="#bcd4cd">
     <rect x="474" y="120" width="30" height="14" rx="2" transform="rotate(-16 474 120)"/>
     <rect x="468" y="158" width="22" height="8" rx="2" transform="rotate(-16 468 158)"/>
    </g>
    <path d="M520 118c-46 0-84-22-84-50s38-50 84-50 84 22 84 50-38 50-84 50Z" fill="none" stroke="#a7c3bc" stroke-width="1" opacity=".35"/>
   </g>
   <g class="layer-mid" data-depth=".55">
    <g fill="#203b3d">
     <rect x="96" y="266" width="44" height="54"/>
     <rect x="150" y="242" width="34" height="78"/>
     <rect x="194" y="280" width="60" height="40"/>
     <rect x="266" y="256" width="42" height="64"/>
    </g>
    <g fill="#3c5c5b">
     <rect x="96" y="262" width="44" height="4"/>
     <rect x="150" y="238" width="34" height="4"/>
     <rect x="194" y="276" width="60" height="4"/>
     <rect x="266" y="252" width="42" height="4"/>
    </g>
    <g stroke="#9fbdb4" stroke-width="3" fill="none" opacity=".65">
     <path d="M330 320V222m0 0-44-24m44 24 44-24"/>
     <path d="M330 222 286 198m44 24 44-24"/>
     <circle cx="330" cy="222" r="5"/>
     <circle cx="286" cy="198" r="4"/><circle cx="374" cy="198" r="4"/>
    </g>
    <g fill="#4b6a5d">
     <path d="M432 320h94l-47-38Z"/>
     <path d="M444 320h70l-35-28Z" fill="#7a9a82" opacity=".7"/>
     <path d="M560 320h112l-56-46Z"/>
     <path d="M574 320h84l-42-34Z" fill="#7a9a82" opacity=".7"/>
    </g>
    <g stroke="#b5cbc0" stroke-width="1.2" fill="none" opacity=".5">
     <path d="M400 298 470 260 520 290 590 246 660 292 730 238"/>
     <circle cx="470" cy="260" r="3"/><circle cx="590" cy="246" r="3"/><circle cx="730" cy="238" r="3"/>
    </g>
    <g stroke="#9fbdb4" stroke-width="2" fill="none" opacity=".45">
     <path d="M730 320V258"/><path d="M730 258l-24-12m24 12 26-9m-26 9 2-25"/>
     <path d="M676 320V274"/><path d="M676 274l-18-8m18 8 19-7m-19 7 1-19"/>
    </g>
   </g>
   <g class="layer-near" data-depth="1">
    <path d="M0 330h800v30H0Z" fill="#0b1b1d"/>
    <g fill="none" stroke="#4e8b83" stroke-width="1.5" opacity=".4">
     <path d="M0 320h70v-20h60v20h64v-28h72v28h66v-18h86v18h70v-24h66v24h80"/>
     <path d="M0 344h100v-12h90v12h100v-16h96v16h110v-10h90v10h214"/>
    </g>
   </g>
  </svg>
 </div>
</div>
