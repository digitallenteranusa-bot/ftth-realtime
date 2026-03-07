<?php

namespace App\Http\Controllers;

use App\Models\Alarm;
use App\Models\Customer;
use App\Models\Ont;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Export customers list as CSV.
     */
    public function customersCsv(): StreamedResponse
    {
        $customers = Customer::orderBy('name')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customers_' . date('Y-m-d') . '.csv"',
        ];

        return new StreamedResponse(function () use ($customers) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['ID', 'Name', 'Address', 'Phone', 'Email', 'NIK', 'Status', 'Notes', 'Created At']);

            foreach ($customers as $customer) {
                fputcsv($handle, [
                    $customer->id,
                    $customer->name,
                    $customer->address,
                    $customer->phone,
                    $customer->email,
                    $customer->nik,
                    $customer->status,
                    $customer->notes,
                    $customer->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export customers list as printable PDF (HTML with print-friendly CSS).
     */
    public function customersPdf()
    {
        $customers = Customer::orderBy('name')->get();

        $title = 'Customers Report';
        $headers = ['ID', 'Name', 'Address', 'Phone', 'Email', 'NIK', 'Status', 'Notes', 'Created At'];
        $rows = $customers->map(function ($customer) {
            return [
                $customer->id,
                $customer->name,
                $customer->address,
                $customer->phone,
                $customer->email,
                $customer->nik,
                $customer->status,
                $customer->notes,
                $customer->created_at?->toDateTimeString(),
            ];
        })->toArray();

        return response()->view('exports.table', compact('title', 'headers', 'rows'))
            ->header('Content-Type', 'text/html');
    }

    /**
     * Export ONTs with customer and ODP relations as CSV.
     */
    public function ontsCsv(): StreamedResponse
    {
        $onts = Ont::with(['customer', 'odp'])->orderBy('name')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="onts_' . date('Y-m-d') . '.csv"',
        ];

        return new StreamedResponse(function () use ($onts) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['ID', 'Name', 'Serial Number', 'ONT ID', 'Status', 'RX Power', 'TX Power', 'Customer', 'ODP', 'Last Online', 'Notes', 'Created At']);

            foreach ($onts as $ont) {
                fputcsv($handle, [
                    $ont->id,
                    $ont->name,
                    $ont->serial_number,
                    $ont->ont_id_number,
                    $ont->status,
                    $ont->rx_power,
                    $ont->tx_power,
                    $ont->customer?->name,
                    $ont->odp?->name ?? $ont->odp?->id,
                    $ont->last_online_at?->toDateTimeString(),
                    $ont->notes,
                    $ont->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export ONTs as printable PDF (HTML with print-friendly CSS).
     */
    public function ontsPdf()
    {
        $onts = Ont::with(['customer', 'odp'])->orderBy('name')->get();

        $title = 'ONTs Report';
        $headers = ['ID', 'Name', 'Serial Number', 'ONT ID', 'Status', 'RX Power', 'TX Power', 'Customer', 'ODP', 'Last Online', 'Notes', 'Created At'];
        $rows = $onts->map(function ($ont) {
            return [
                $ont->id,
                $ont->name,
                $ont->serial_number,
                $ont->ont_id_number,
                $ont->status,
                $ont->rx_power,
                $ont->tx_power,
                $ont->customer?->name,
                $ont->odp?->name ?? $ont->odp?->id,
                $ont->last_online_at?->toDateTimeString(),
                $ont->notes,
                $ont->created_at?->toDateTimeString(),
            ];
        })->toArray();

        return response()->view('exports.table', compact('title', 'headers', 'rows'))
            ->header('Content-Type', 'text/html');
    }

    /**
     * Export alarms as CSV.
     */
    public function alarmsCsv(): StreamedResponse
    {
        $alarms = Alarm::with('resolvedByUser')->orderByDesc('created_at')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="alarms_' . date('Y-m-d') . '.csv"',
        ];

        return new StreamedResponse(function () use ($alarms) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['ID', 'Device Type', 'Device ID', 'Severity', 'Title', 'Description', 'Resolved', 'Resolved At', 'Resolved By', 'Created At']);

            foreach ($alarms as $alarm) {
                fputcsv($handle, [
                    $alarm->id,
                    $alarm->device_type,
                    $alarm->device_id,
                    $alarm->severity,
                    $alarm->title,
                    $alarm->description,
                    $alarm->is_resolved ? 'Yes' : 'No',
                    $alarm->resolved_at?->toDateTimeString(),
                    $alarm->resolvedByUser?->name,
                    $alarm->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
