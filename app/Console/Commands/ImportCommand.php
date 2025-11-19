<?php

namespace App\Console\Commands;

use App\Models\Address;
use App\Models\Email;
use App\Models\Name;
use App\Models\Passport;
use App\Models\Phone;
use App\Models\Vehicle;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import {type} {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import clean records';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        try {
            $builder = $this->builder($this->argument('type'));
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return;
        }

        $filename = $this->argument('file');

        if (! file_exists($filename)) {
            $this->error("File {$filename} not found");
            return;
        }

        $handle = fopen($filename, 'r');

        if ($handle === false) {
            $this->error("Unable to open file {$filename}");
            return;
        }

        $header = [];

        while (($line = fgetcsv($handle)) !== false) {

            if (! $header) {
                $header = $line;

                if (! in_array('source', $header)) {
                    $this->error("First row must be a header and contains, at least, source column.");
                    return;
                }

                continue;

            }

            $row = array_combine($header, $line);

            $source = Arr::get($row, 'source');
            $response = Arr::except($row, 'source');

            if ($builder->clone()->whereKey($source)->doesntExist()) {
                $builder->clone()->insert([
                    'source'   => $source,
                    'response' => $response
                ]);
                $this->info($source);
            } else {
                $this->warn($source);
            }
        }

        fclose($handle);

        $this->info('Complete');
    }

    /**
     * @throws Exception
     */
    protected function builder(string $type): Builder
    {
        return match ($type) {
            'address'  => Address::query(),
            'email'    => Email::query(),
            'name'     => Name::query(),
            'passport' => Passport::query(),
            'phone'    => Phone::query(),
            'vehicle'  => Vehicle::query(),
            default    => throw new Exception("Unknown import type {$type}"),
        };
    }
}
