<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppAccountResource\Pages;
use App\Jobs\SyncConversationsJob;
use App\Models\WhatsAppAccount;
use App\Services\WhatsAppGateway;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WhatsAppAccountResource extends Resource
{
    protected static ?string $model = WhatsAppAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'WhatsApp Accounts';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('label')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('phone_number')
                ->label('Phone number')
                ->tel()
                ->maxLength(32),
            Forms\Components\Select::make('status')
                ->disabled()
                ->options([
                    'disconnected' => 'Disconnected',
                    'waiting_for_qr' => 'Waiting for QR',
                    'connected' => 'Connected',
                    'error' => 'Error',
                ]),
            Forms\Components\Textarea::make('last_error')
                ->disabled()
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_connected_at')
                    ->dateTime()
                    ->label('Last connected'),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('refreshStatus')
                    ->label('Refresh status')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (WhatsAppAccount $record) {
                        /** @var WhatsAppGateway $gateway */
                        $gateway = app(WhatsAppGateway::class);

                        $data = $gateway->getSessionStatus($record);

                        if (! $data) {
                            Notification::make()
                                ->title('Could not refresh status from WhatsApp service.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $status = $data['status'] ?? $record->status;

                        $record->forceFill([
                            'status' => $status,
                            'last_connected_at' => $status === 'connected'
                                ? now()
                                : $record->last_connected_at,
                        ])->save();

                        Notification::make()
                            ->title("Status updated to {$status}.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('connect')
                    ->label('Connect / Show QR')
                    ->icon('heroicon-o-qr-code')
                    ->modalHeading('Connect WhatsApp')
                    ->modalSubmitAction(false)
                    ->modalContent(function (WhatsAppAccount $record) {
                        /** @var WhatsAppGateway $gateway */
                        $gateway = app(WhatsAppGateway::class);

                        $record->forceFill([
                            'status' => 'waiting_for_qr',
                        ])->save();

                        $response = $gateway->startSession($record);

                        if (! empty($response['session_ref'])) {
                            $record->forceFill([
                                'session_ref' => $response['session_ref'],
                                'status' => $response['status'] ?? 'waiting_for_qr',
                            ])->save();
                        }

                        $qr = $gateway->fetchQrCode($record);

                        if (! $qr) {
                            return view('filament.whatsapp.no-qr');
                        }

                        return view('filament.whatsapp.qr', [
                            'qr' => $qr,
                        ]);
                    }),
                Tables\Actions\Action::make('syncConversations')
                    ->label('Sync conversations')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function (WhatsAppAccount $record) {
                        SyncConversationsJob::dispatch($record);

                        Notification::make()
                            ->title('Sync started')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsAppAccounts::route('/'),
            'create' => Pages\CreateWhatsAppAccount::route('/create'),
            'edit' => Pages\EditWhatsAppAccount::route('/{record}/edit'),
        ];
    }
}

