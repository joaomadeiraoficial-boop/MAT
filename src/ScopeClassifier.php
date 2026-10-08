<?php
final class ScopeClassifier {
    public const VERSION = 'scope-rules-2.0';

    /** @var array<int,array<string,mixed>> */
    private static array $customRules = [];

    public static function configure(PDO $pdo): void {
        try {
            self::$customRules = $pdo->query(
                "SELECT pattern,natureza,weight,field_scope,language FROM scope_keyword_rules WHERE enabled=1 ORDER BY weight DESC,id ASC"
            )->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            self::$customRules = [];
        }
    }

    public static function builtinRuleCount(): int {
        $n = 0;
        foreach (self::builtinRules() as $group) $n += count($group);
        return $n + count(self::categoryPriors());
    }

    public static function classify(string $name, string $description = '', string $category = ''): array {
        $fields = [
            'name' => Normalizer::key($name),
            'category' => Normalizer::key($category),
            'description' => Normalizer::key($description),
        ];
        $scores = [
            'tecnologico'=>0,
            'descoberta_cientifica'=>0,
            'metodo_infraestrutura'=>0,
            'social_politico'=>0,
            'biologico_evolutivo'=>0,
            'cosmologico'=>0,
        ];
        $evidence = [];

        // Categoria é um sinal muito forte em bases científicas estruturadas.
        $cat = $fields['category'];
        foreach (self::categoryPriors() as $pattern => $rule) {
            if (self::containsPhrase($cat, $pattern)) {
                [$nature,$weight] = $rule;
                $scores[$nature] += $weight;
                $evidence[] = ['score'=>$weight,'text'=>"categoria: {$pattern}"];
            }
        }

        // Nome vale mais que descrição porque costuma representar diretamente o marco.
        $multipliers = ['name'=>3, 'category'=>2, 'description'=>1];
        foreach (self::builtinRules() as $nature => $rules) {
            foreach ($rules as $pattern => $weight) {
                foreach ($fields as $field => $text) {
                    if ($text === '' || !self::containsPhrase($text, $pattern)) continue;
                    $points = (int)$weight * $multipliers[$field];
                    $scores[$nature] += $points;
                    $evidence[] = ['score'=>$points,'text'=>self::fieldLabel($field) . ': ' . $pattern];
                }
            }
        }

        // Regras personalizadas adicionadas pelo usuário no Dicionário de triagem.
        foreach (self::$customRules as $rule) {
            $nature = (string)($rule['natureza'] ?? '');
            if (!array_key_exists($nature,$scores)) continue;
            $pattern = Normalizer::key((string)($rule['pattern'] ?? ''));
            if ($pattern === '') continue;
            $scope = (string)($rule['field_scope'] ?? 'any');
            $weight = max(1,min(20,(int)($rule['weight'] ?? 3)));
            $targets = $scope === 'name' ? ['name'] : ($scope === 'category' ? ['category'] : ($scope === 'description' ? ['description'] : ['name','category','description']));
            foreach ($targets as $field) {
                if (!self::containsPhrase($fields[$field],$pattern)) continue;
                $points = $weight * $multipliers[$field];
                $scores[$nature] += $points;
                $evidence[] = ['score'=>$points,'text'=>'regra própria/' . self::fieldLabel($field) . ': ' . $pattern];
            }
        }

        // Heurística estrutural: nomes de invenção normalmente contêm um substantivo técnico.
        $technicalNouns = [
            'device','apparatus','machine','engine','motor','system','process','method','technique','technology','instrument','tool','equipment',
            'circuit','sensor','detector','scanner','meter','generator','reactor','battery','cell','pump','valve','turbine','compressor','transmitter',
            'receiver','processor','computer','software','algorithm','protocol','network','material','alloy','polymer','drug','vaccine','therapy','test',
            'dispositivo','aparato','maquina','motor','sistema','processo','metodo','tecnica','tecnologia','instrumento','ferramenta','equipamento',
            'circuito','sensor','detector','scanner','gerador','reator','bateria','bomba','valvula','turbina','compressor','processador','computador',
            'software','algoritmo','protocolo','rede','material','liga','polimero','farmaco','vacina','terapia','teste'
        ];
        $genericHits = 0;
        foreach ($technicalNouns as $word) if (self::containsPhrase($fields['name'],$word)) $genericHits++;
        if ($genericHits > 0) {
            $bonus = min(6,$genericHits * 2);
            $scores['tecnologico'] += $bonus;
            $evidence[] = ['score'=>$bonus,'text'=>'estrutura do nome: vocabulário técnico'];
        }

        // “Theory/law/discovery” no nome, acompanhado de área científica, favorece descoberta científica.
        $scienceMarkers = ['theory','law','principle','equation','discovery','phenomenon','structure','constant','effect','teoria','lei','principio','equacao','descoberta','fenomeno','estrutura','constante','efeito'];
        $scienceMarkerHit = false;
        foreach ($scienceMarkers as $m) if (self::containsPhrase($fields['name'],$m)) { $scienceMarkerHit=true; break; }
        if ($scienceMarkerHit && $scores['descoberta_cientifica'] > 0) {
            $scores['descoberta_cientifica'] += 4;
            $evidence[] = ['score'=>4,'text'=>'nome com marcador de descoberta/teoria'];
        }

        $positive = $scores['tecnologico'] + $scores['descoberta_cientifica'] + $scores['metodo_infraestrutura'];
        $outside = $scores['social_politico'] + $scores['biologico_evolutivo'] + $scores['cosmologico'];
        $margin = $positive - $outside;

        $nature = 'nao_classificado';
        $max = max($scores);
        if ($max > 0) {
            $leaders = array_keys(array_filter($scores, fn($v) => $v === $max));
            $nature = count($leaders) > 1 ? 'misto' : $leaders[0];
        }
        if ($positive >= 6 && $outside >= 6 && abs($margin) < 8) $nature = 'misto';

        if ($positive >= 8 && $margin >= 5) {
            $suggestion = 'sim';
        } elseif ($outside >= 8 && $margin <= -5) {
            $suggestion = 'nao';
        } else {
            $suggestion = 'revisar';
        }

        $winner = max($positive,$outside);
        if ($suggestion === 'revisar') {
            $confidence = 'baixa';
        } elseif (abs($margin) >= 14 || $winner >= 24) {
            $confidence = 'alta';
        } elseif (abs($margin) >= 7 || $winner >= 14) {
            $confidence = 'media';
        } else {
            $confidence = 'baixa';
        }

        usort($evidence, fn($a,$b) => $b['score'] <=> $a['score']);
        $top = [];
        foreach ($evidence as $item) {
            if (!in_array($item['text'],$top,true)) $top[] = $item['text'];
            if (count($top) >= 6) break;
        }
        $reason = sprintf(
            'Triagem v2: positivo=%d, fora=%d, saldo=%+d, confiança=%s.%s A decisão final continua humana.',
            $positive,$outside,$margin,$confidence,
            $top ? ' Evidências: ' . implode('; ',$top) . '.' : ''
        );

        return [
            'natureza' => $nature,
            'sugestao_elegibilidade' => $suggestion,
            'motivo_escopo' => $reason,
            'classificador_versao' => self::VERSION,
            'scope_score' => $margin,
            'scope_confidence' => $confidence,
        ];
    }

    private static function categoryPriors(): array {
        return [
            // Categorias comuns de bases de inovação — inclusive DataS1.
            'industrial'=>['tecnologico',14], 'industry'=>['tecnologico',14], 'agricultural'=>['tecnologico',13], 'agriculture'=>['tecnologico',13],
            'medical'=>['tecnologico',14], 'medicine'=>['tecnologico',14], 'healthcare'=>['tecnologico',13], 'transportation'=>['tecnologico',14], 'transport'=>['tecnologico',14],
            'communication'=>['tecnologico',14], 'communications'=>['tecnologico',14], 'telecommunications'=>['tecnologico',14], 'household'=>['tecnologico',11],
            'armaments'=>['tecnologico',12], 'military technology'=>['tecnologico',12], 'engineering'=>['tecnologico',14], 'electronics'=>['tecnologico',14],
            'computing'=>['tecnologico',14], 'information technology'=>['tecnologico',14], 'energy'=>['tecnologico',13], 'manufacturing'=>['tecnologico',13],
            'materials'=>['tecnologico',12], 'materials engineering'=>['tecnologico',13], 'aerospace'=>['tecnologico',14], 'aviation'=>['tecnologico',13],
            'biotechnology'=>['tecnologico',14], 'pharmaceutical'=>['tecnologico',13], 'pharmaceuticals'=>['tecnologico',13],
            'industrial technology'=>['tecnologico',15], 'technology'=>['tecnologico',12], 'invention'=>['tecnologico',14], 'innovation'=>['tecnologico',12],
            'fisica'=>['descoberta_cientifica',12], 'physics'=>['descoberta_cientifica',12], 'chemistry'=>['descoberta_cientifica',12], 'quimica'=>['descoberta_cientifica',12],
            'mathematics'=>['descoberta_cientifica',12], 'matematica'=>['descoberta_cientifica',12], 'astronomy'=>['descoberta_cientifica',11], 'astronomia'=>['descoberta_cientifica',11],
            'geology'=>['descoberta_cientifica',11], 'geologia'=>['descoberta_cientifica',11], 'genetics'=>['descoberta_cientifica',12], 'genetica'=>['descoberta_cientifica',12],
            'microbiology'=>['descoberta_cientifica',12], 'microbiologia'=>['descoberta_cientifica',12], 'neuroscience'=>['descoberta_cientifica',12], 'scientific discovery'=>['descoberta_cientifica',15],
            'research infrastructure'=>['metodo_infraestrutura',13], 'infraestrutura cientifica'=>['metodo_infraestrutura',13], 'scientific method'=>['metodo_infraestrutura',15],
            'politics'=>['social_politico',14], 'political'=>['social_politico',14], 'social history'=>['social_politico',12], 'historical event'=>['social_politico',11],
            'natural history event'=>['biologico_evolutivo',12], 'evolutionary event'=>['biologico_evolutivo',13], 'cosmological event'=>['cosmologico',14],
        ];
    }

    private static function builtinRules(): array {
        return [
            'tecnologico' => self::weighted([
                // Computação / informação
                'computer','computing','digital computer','programmable computer','supercomputer','mainframe','microcomputer','personal computer','quantum computer',
                'processor','microprocessor','cpu','gpu','integrated circuit','microchip','chip','transistor','semiconductor','vacuum tube','logic gate','memory','ram','rom','flash memory','eeprom',
                'software','operating system','database','spreadsheet','word processor','algorithm','cryptography','encryption','public key cryptography','artificial intelligence','machine learning','neural network','deep learning','large language model',
                'internet','world wide web','www','web browser','search engine','email','e mail','ethernet','tcp','ip protocol','network protocol','router','modem','fiber optics','optical fiber','barcode','qr code',
                'computador','computacao','computador digital','computador programavel','supercomputador','microcomputador','computador pessoal','processador','microprocessador','circuito integrado','microchip','transistor','semicondutor','valvula a vacuo','porta logica','memoria flash','software','sistema operacional','banco de dados','planilha','algoritmo','criptografia','inteligencia artificial','aprendizado de maquina','rede neural','internet','navegador','mecanismo de busca','correio eletronico','fibra optica',
                // Energia / elétrica / eletrônica
                'electricity','electric','electrical','electronic','electromagnetic','battery','chemical battery','storage battery','lithium ion battery','solar cell','photovoltaic','solar battery','generator','dynamo','transformer','alternating current','direct current','power grid','electric motor','electric light','light bulb','led','laser','maser','radio','radar','microwave','antenna','sensor','photodiode','oscilloscope','printed circuit board',
                'eletricidade','eletrico','eletrica','eletronico','eletromagnetico','bateria','celula solar','fotovoltaico','gerador','dinamo','transformador','corrente alternada','corrente continua','rede eletrica','motor eletrico','lampada','laser','radio','radar','microondas','antena','sensor','placa de circuito impresso',
                // Máquinas / indústria / materiais
                'steam engine','steam pump','steam turbine','engine','internal combustion engine','diesel engine','jet engine','gas turbine','turbine','pump','compressor','lathe','machine tool','assembly line','mass production','interchangeable parts','factory system','industrial robot','robot','robotics','automation','conveyor belt','3d printing','additive manufacturing','cnc','numerical control',
                'steelmaking','bessemer process','stainless steel','aluminum production','aluminium production','electroplating','vulcanized rubber','plastic','bakelite','polyethylene','polymer','nylon','rayon','cellophane','kevlar','carbon fiber','graphene','cement','concrete','alloy','synthetic rubber','ceramic','semiconductor material',
                'maquina a vapor','bomba a vapor','turbina a vapor','motor','motor a combustao','motor diesel','motor a jato','turbina','bomba','compressor','torno','maquina ferramenta','linha de montagem','producao em massa','pecas intercambiaveis','sistema fabril','robo industrial','robotica','automacao','impressao 3d','manufatura aditiva','aco inoxidavel','producao de aluminio','galvanoplastia','borracha vulcanizada','plastico','polietileno','polimero','fibra de carbono','cimento','concreto','liga metalica',
                // Transporte / navegação / aeroespacial
                'wheel','compass','marine chronometer','railway','railroad','locomotive','steam locomotive','diesel locomotive','electric locomotive','automobile','car','motorcycle','bicycle','airplane','aeroplane','aircraft','helicopter','jet airplane','rocket','spacecraft','satellite','gps','navigation system','autopilot','hovercraft','hydrofoil','submarine','ship propeller','steamboat','cable car','tramway','streetcar','pneumatic tire','air brake','disc brake','drum brake','automatic transmission','fuel injection','carburetor','spark plug','traffic signal','speedometer','altimeter',
                'roda','bussola','cronometro marinho','ferrovia','locomotiva','automovel','carro','motocicleta','bicicleta','aviao','aeronave','helicoptero','foguete','nave espacial','satelite','gps','sistema de navegacao','piloto automatico','submarino','helice naval','barco a vapor','bonde','pneu','freio a ar','freio a disco','transmissao automatica','injecao de combustivel','carburador','vela de ignicao','semaforo','velocimetro','altimetro',
                // Comunicação / mídia
                'printing press','printing','movable type','telegraph','electric telegraph','telephone','mobile phone','cell phone','smartphone','facsimile','fax','television','phonograph','gramophone','photography','camera','digital camera','cinematography','motion picture','typewriter','qwerty','magnetic recording','wire recorder','tape recorder','satellite communication','wireless','radio transmission','frequency modulation','fm radio','cable tv','remote control','caller id','laser printer','xerography',
                'imprensa','impressao','tipos moveis','telegrafo','telefone','telefone celular','celular','smartphone','fax','televisao','fonografo','fotografia','camera','camera digital','cinematografia','maquina de escrever','gravacao magnetica','comunicacao por satelite','sem fio','transmissao de radio','modulacao de frequencia','controle remoto','identificador de chamadas','impressora laser','xerografia',
                // Medicina / biotecnologia
                'vaccine','vaccination','antibiotic','penicillin','sulfa drug','tetracycline','nystatin','chemotherapy','anesthesia','anaesthesia','antiseptic','pasteurization','x ray','radiography','radiation therapy','ultrasound','electrocardiogram','ecg','eeg','electroencephalogram','mri','magnetic resonance','ct scan','cat scan','tomography','endoscope','stethoscope','syringe','hypodermic needle','defibrillator','pacemaker','dialysis','artificial kidney','heart lung machine','blood bank','blood transfusion','organ transplant','heart transplant','kidney transplant','cochlear implant','contact lens','laser eye surgery','in vitro fertilization','ivf','dna sequencing','genome sequencing','pcr','crispr','gene editing','recombinant dna','monoclonal antibody','insulin','oral contraceptive','medical imaging','diagnostic test','blood pressure monitor','respirator','ventilator','stent','catheter',
                'vacina','vacinacao','antibiotico','penicilina','quimioterapia','anestesia','antisseptico','pasteurizacao','raio x','radiografia','radioterapia','ultrassom','eletrocardiograma','eletroencefalograma','ressonancia magnetica','tomografia','endoscopio','estetoscopio','seringa','agulha hipodermica','desfibrilador','marcapasso','dialise','rim artificial','banco de sangue','transfusao de sangue','transplante','implante coclear','lente de contato','fertilizacao in vitro','sequenciamento de dna','sequenciamento do genoma','edicao genetica','dna recombinante','anticorpo monoclonal','insulina','contraceptivo oral','imagem medica','teste diagnostico','monitor de pressao','respirador','ventilador','stent','cateter',
                // Agricultura / alimentos
                'seed drill','plow','plough','steel plow','moldboard plow','reaper','harvester','combine harvester','threshing machine','tractor','fertilizer','artificial fertilizer','superphosphate','pesticide','herbicide','insecticide','irrigation','crop rotation','hybrid corn','green revolution','mechanical harvester','milking machine','grain elevator','hay baler','canning','refrigeration','refrigerator','quick freezing','food preservation','pasteurizer',
                'semeadeira','arado','arado de aco','ceifadeira','colheitadeira','debulhadora','trator','fertilizante','fertilizante artificial','superfosfato','pesticida','herbicida','inseticida','irrigacao','rotacao de culturas','milho hibrido','revolucao verde','ordenhadeira','elevador de graos','enfardadeira','enlatamento','refrigeracao','geladeira','congelamento rapido','conservacao de alimentos',
                // Instrumentos e tecnologias diversas
                'thermometer','microscope','telescope','spectrometer','spectroscope','mass spectrometer','electron microscope','accelerator','particle accelerator','cyclotron','seismograph','barometer','chronometer','calculator','mechanical calculator','difference engine','analytical engine','printing machine','sewing machine','washing machine','dishwasher','vacuum cleaner','air conditioner','refrigerator','microwave oven','elevator','escalator','safety razor','ballpoint pen','mechanical pencil','zipper','velcro','post it','bubble wrap',
                'termometro','microscopio','telescopio','espectrometro','espectroscopio','microscopio eletronico','acelerador de particulas','ciclotron','sismografo','barometro','cronometro','calculadora','maquina de costura','maquina de lavar','lava loucas','aspirador','ar condicionado','geladeira','forno de microondas','elevador','escada rolante','caneta esferografica','lapiseira'
            ], 2, [
                'transistor','integrated circuit','microprocessor','internet','world wide web','artificial intelligence','penicillin','vaccine','steam engine','electricity','airplane','laser','gps','satellite','crispr','dna sequencing','printing press','telephone','radio','television','computer','semiconductor','foguete','computador','internet','penicilina','vacina','motor a vapor','eletricidade','aviao','satelite','telefone','televisao','radio'
            ], 4),

            'descoberta_cientifica' => self::weighted([
                'scientific method','gravity','gravitation','law of gravity','newton laws','laws of motion','calculus','derivative','integral','analytic geometry','non euclidean geometry','probability theory','statistics','number theory','set theory','boolean algebra','information theory','game theory',
                'periodic table','atomic theory','atom','electron','proton','neutron','atomic nucleus','radioactivity','electromagnetism','electromagnetic induction','maxwell equations','thermodynamics','entropy','quantum mechanics','quantum theory','quantum electrodynamics','relativity','special relativity','general relativity','photoelectric effect','wave particle duality','uncertainty principle','standard model','higgs boson','superconductivity','semiconductor theory',
                'germ theory','theory of germs','cell theory','theory of cells','dna structure','double helix','genetic code','chromosome','gene','genetics','natural selection','evolution theory','theory of evolution','plate tectonics','continental drift','microorganism','bacteria','virus','blood circulation','oxygen','chemical element','molecule','chemical bond','periodicity','radioactive decay','nuclear fission','nuclear fusion','cosmic microwave background','expansion of universe','exoplanet',
                'metodo cientifico','gravidade','lei da gravidade','leis do movimento','calculo','derivada','integral','geometria analitica','geometria nao euclidiana','teoria da probabilidade','estatistica','teoria dos numeros','teoria dos conjuntos','algebra booleana','teoria da informacao','teoria dos jogos',
                'tabela periodica','teoria atomica','atomo','eletron','proton','neutron','nucleo atomico','radioatividade','eletromagnetismo','inducao eletromagnetica','equacoes de maxwell','termodinamica','entropia','mecanica quantica','teoria quantica','relatividade','relatividade especial','relatividade geral','efeito fotoeletrico','dualidade onda particula','principio da incerteza','modelo padrao','boson de higgs','supercondutividade',
                'teoria germinal','teoria dos germes','teoria celular','teoria das celulas','estrutura do dna','dupla helice','codigo genetico','cromossomo','gene','genetica','selecao natural','teoria da evolucao','tectonica de placas','deriva continental','microorganismo','bacteria','virus','circulacao sanguinea','oxigenio','elemento quimico','molecula','ligacao quimica','decaimento radioativo','fissao nuclear','fusao nuclear','radiacao cosmica de fundo','expansao do universo','exoplaneta'
            ], 2, [
                'scientific method','relativity','theory of relativity','quantum mechanics','periodic table','dna structure','genetic code','germ theory','electromagnetism','thermodynamics','natural selection','plate tectonics','metodo cientifico','relatividade','mecanica quantica','tabela periodica','estrutura do dna','codigo genetico','teoria germinal','eletromagnetismo','termodinamica','selecao natural','tectonica de placas'
            ], 4),

            'metodo_infraestrutura' => self::weighted([
                'laboratory','research laboratory','research institute','scientific institute','observatory','university research','peer review','scientific journal','academic journal','experimental method','controlled experiment','randomized controlled trial','clinical trial','double blind','metrology','standardization','international system of units','si units','scientific database','data repository','genome project','human genome project','cern','large hadron collider','particle accelerator','synchrotron','space telescope','radio telescope','research reactor','supercomputing center','sequencing center',
                'laboratorio','laboratorio de pesquisa','instituto de pesquisa','instituto cientifico','observatorio','pesquisa universitaria','revisao por pares','periodico cientifico','metodo experimental','experimento controlado','ensaio clinico','duplo cego','metrologia','padronizacao','sistema internacional de unidades','banco de dados cientifico','repositorio de dados','projeto genoma','projeto genoma humano','acelerador de particulas','sincrotron','telescopio espacial','radiotelescopio','reator de pesquisa','centro de supercomputacao','centro de sequenciamento'
            ], 2, ['scientific method','peer review','randomized controlled trial','cern','large hadron collider','human genome project','metodo cientifico','revisao por pares','ensaio clinico','projeto genoma humano'], 4),

            'social_politico' => self::weighted([
                'world war','first world war','second world war','cold war','civil war','war begins','war ends','battle','revolution','political revolution','coup','election','constitution','treaty','peace treaty','declaration of independence','independence movement','empire','dynasty','kingdom','monarchy','democracy','parliament','political party','united nations','league of nations','berlin wall','fall of berlin wall','fall of rome','roman empire','city state','legal code','law code','hammurabi','colonization','decolonization','slavery','abolition','economic crisis','stock market crash','great depression',
                'guerra mundial','primeira guerra mundial','segunda guerra mundial','guerra fria','guerra civil','batalha','revolucao politica','golpe','eleicao','constituicao','tratado','tratado de paz','declaracao de independencia','movimento de independencia','imperio','dinastia','reino','monarquia','democracia','parlamento','partido politico','nacoes unidas','liga das nacoes','muro de berlim','queda do muro de berlim','queda de roma','imperio romano','cidade estado','codigo legal','codigo de hamurabi','colonizacao','descolonizacao','escravidao','abolicao','crise economica','quebra da bolsa','grande depressao'
            ], 3, ['world war','cold war','political revolution','coup','election','treaty','berlin wall','fall of rome','guerra mundial','guerra fria','revolucao politica','golpe','eleicao','tratado','muro de berlim','queda de roma'], 4),

            'biologico_evolutivo' => self::weighted([
                'origin of life','first life','first cells','first multicellular life','emergence of multicellular life','first mammals','first primates','first hominids','homo sapiens emerges','appearance of homo sapiens','human migration','out of africa','species emergence','mass extinction','dinosaur extinction','first burial','prehistoric burial','biological evolution event',
                'origem da vida','primeira vida','primeiras celulas','primeira vida multicelular','surgimento da vida multicelular','primeiros mamiferos','primeiros primatas','primeiros hominideos','surgimento do homo sapiens','aparicao do homo sapiens','migracao humana','saida da africa','surgimento de especie','extincao em massa','extincao dos dinossauros','primeiro sepultamento','sepultamento pre historico','evento evolutivo biologico'
            ], 3, ['origin of life','homo sapiens emerges','mass extinction','origem da vida','surgimento do homo sapiens','extincao em massa'], 5),

            'cosmologico' => self::weighted([
                'big bang','formation of universe','formation of galaxies','galaxy formation','star formation','formation of solar system','solar system formation','formation of earth','earth formation','formation of moon','moon formation','cosmic event','supernova event','meteor impact event',
                'big bang','formacao do universo','formacao de galaxias','formacao de estrelas','formacao do sistema solar','formacao da terra','formacao da lua','evento cosmologico','evento de supernova','impacto de meteoro'
            ], 4, ['big bang','formation of universe','formation of solar system','formation of earth','formacao do universo','formacao do sistema solar','formacao da terra'], 6),
        ];
    }

    private static function weighted(array $normal, int $normalWeight, array $strong = [], int $strongWeight = 4): array {
        $out = [];
        foreach ($normal as $p) $out[Normalizer::key($p)] = $normalWeight;
        foreach ($strong as $p) $out[Normalizer::key($p)] = $strongWeight;
        return $out;
    }

    private static function containsPhrase(string $text, string $pattern): bool {
        $text = trim($text);
        $pattern = Normalizer::key($pattern);
        if ($text === '' || $pattern === '') return false;
        return str_contains(' ' . $text . ' ', ' ' . $pattern . ' ');
    }

    private static function fieldLabel(string $field): string {
        return match($field) {
            'name'=>'nome', 'category'=>'categoria', 'description'=>'descrição', default=>$field
        };
    }
}
