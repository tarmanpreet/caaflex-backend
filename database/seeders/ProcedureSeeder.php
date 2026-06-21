<?php

namespace Database\Seeders;

use App\Models\PracticeType;
use App\Models\Procedure;
use Illuminate\Database\Seeder;

class ProcedureSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPracticeTypes();
        $this->seedProcedures();
    }

    private function seedPracticeTypes(): void
    {
        $types = [
            ['name' => '730 - Dichiarazione dei Redditi', 'duration_minutes' => 60, 'color' => '#3B82F6'],
            ['name' => 'ISEE - Attestazione',             'duration_minutes' => 30, 'color' => '#10B981'],
            ['name' => 'Successione',                     'duration_minutes' => 90, 'color' => '#F59E0B'],
            ['name' => 'IMU/TASI',                        'duration_minutes' => 30, 'color' => '#8B5CF6'],
            ['name' => 'RED - Redditi Pensionati',        'duration_minutes' => 45, 'color' => '#EC4899'],
            ['name' => 'Bonus Edilizi',                   'duration_minutes' => 60, 'color' => '#14B8A6'],
        ];

        foreach ($types as $type) {
            PracticeType::firstOrCreate(['name' => $type['name']], $type);
        }
    }

    private function seedProcedures(): void
    {
        $procedures = [
            [
                'type_name' => '730 - Dichiarazione dei Redditi',
                'name' => '730 Semplice',
                'default_notes' => 'Portare: documento d\'identità, codice fiscale, CUD/UNIEMENS, spese sanitarie, spese istruzione, mutuo/affitto.',
            ],
            [
                'type_name' => '730 - Dichiarazione dei Redditi',
                'name' => '730 con Detrazioni',
                'default_notes' => 'Portare: documento d\'identità, codice fiscale, CUD, spese detraibili (sanitarie, istruzione, interessi mutuo, donazioni).',
            ],
            [
                'type_name' => '730 - Dichiarazione dei Redditi',
                'name' => '730 per Lavoratore Dipendente',
                'default_notes' => 'Portare: documento d\'identità, codice fiscale, CUD, documentazione spese.',
            ],
            [
                'type_name' => 'ISEE - Attestazione',
                'name' => 'ISEE Standard',
                'default_notes' => 'Portare: documento d\'identità, codice fiscale di tutti i componenti, redditi anno precedente (CUD, pensioni, etc.), patrimonio mobiliare/immobiliare.',
            ],
            [
                'type_name' => 'ISEE - Attestazione',
                'name' => 'ISEE Corrente',
                'default_notes' => 'Portare: documento d\'identità, codice fiscale, redditi ultimi 12 mesi, patrimonio.',
            ],
            [
                'type_name' => 'ISEE - Attestazione',
                'name' => 'ISEE Università',
                'default_notes' => 'Portare: documento d\'identità, codice fiscale studente e componenti nucleo, redditi, patrimonio, iscrizione università.',
            ],
            [
                'type_name' => 'Successione',
                'name' => 'Successione Semplice',
                'default_notes' => 'Portare: certificato di morte, documenti d\'identità eredi, codici fiscali, atto di ultima volontà (se esiste), documenti beni.',
            ],
            [
                'type_name' => 'Successione',
                'name' => 'Successione con Immobili',
                'default_notes' => 'Portare: certificato di morte, documenti eredi, planimetria catastale, visura catastale, atto di acquisto del defunto.',
            ],
            [
                'type_name' => 'Successione',
                'name' => 'Successione con Conti Bancari',
                'default_notes' => 'Portare: certificato di morte, documenti eredi, estratti conto bancari/postali del defunto.',
            ],
            [
                'type_name' => 'IMU/TASI',
                'name' => 'IMU Prima Casa',
                'default_notes' => 'Portare: documento d\'identità, codice fiscale, visura catastale, contratto di residenza.',
            ],
            [
                'type_name' => 'IMU/TASI',
                'name' => 'IMU Seconda Casa',
                'default_notes' => 'Portare: documento d\'identità, codice fiscale, visura catastale, rendita catastale.',
            ],
            [
                'type_name' => 'IMU/TASI',
                'name' => 'TASI',
                'default_notes' => 'Portare: documento d\'identità, codice fiscale, dati immobile, contratto locazione (se applicabile).',
            ],
            [
                'type_name' => 'RED - Redditi Pensionati',
                'name' => 'RED Standard',
                'default_notes' => 'Portare: documento d\'identità, codice fiscale, certificazione redditi pensione, documentazione eventuali altri redditi.',
            ],
            [
                'type_name' => 'RED - Redditi Pensionati',
                'name' => 'RED con Coniuge a Carico',
                'default_notes' => 'Portare: documento d\'identità, codice fiscale pensionato e coniuge, certificazione pensione, documentazione coniuge.',
            ],
            [
                'type_name' => 'Bonus Edilizi',
                'name' => 'Superbonus 110%',
                'default_notes' => 'Portare: documento d\'identità, codice fiscale, documentazione intervento, attestazione asseverazione, fatture pagate.',
            ],
            [
                'type_name' => 'Bonus Edilizi',
                'name' => 'Ecobonus',
                'default_notes' => 'Portare: documento d\'identità, codice fiscale, attestazione asseverazione, fatture interventi energetici, documentazione pagamenti.',
            ],
            [
                'type_name' => 'Bonus Edilizi',
                'name' => 'Sismabonus',
                'default_notes' => 'Portare: documento d\'identità, codice fiscale, attestazione asseverazione interventi antisismici, fatture pagate.',
            ],
            [
                'type_name' => 'Bonus Edilizi',
                'name' => 'Bonus Facciate',
                'default_notes' => 'Portare: documento d\'identità, codice fiscale, foto stato di fatto facciata, fatture lavori, documentazione pagamenti.',
            ],
        ];

        foreach ($procedures as $procedureData) {
            $practiceType = PracticeType::where('name', $procedureData['type_name'])->first();
            
            if ($practiceType) {
                Procedure::firstOrCreate(
                    [
                        'procedure_type_id' => $practiceType->id,
                        'name' => $procedureData['name'],
                    ],
                    [
                        'default_notes' => $procedureData['default_notes'],
                    ]
                );
            }
        }

        $this->command->info('Procedure seeded successfully.');
    }
}
