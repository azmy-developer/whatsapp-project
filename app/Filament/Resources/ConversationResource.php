<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConversationResource\Pages;
use App\Jobs\GenerateCustomerSummaryJob;
use App\Jobs\SyncMessagesJob;
use App\Models\Conversation;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $navigationLabel = 'Conversations';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('display_name')
                ->disabled(),
            Forms\Components\TextInput::make('phone')
                ->disabled(),
            Forms\Components\Select::make('customer_id')
                ->label('Linked customer')
                ->options(Customer::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Conversation')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_message_at')
                    ->label('Last message')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('linked')
                    ->label('Linked to customer')
                    ->query(function ($query, $state) {
                        if ($state === 'true') {
                            return $query->whereNotNull('customer_id');
                        } elseif ($state === 'false') {
                            return $query->whereNull('customer_id');
                        }
                        return $query; // 'All' الحالة الافتراضية
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('linkCustomer')
                    ->label('Link customer')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->options(Customer::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Conversation $record, array $data) {
                        $record->update([
                            'customer_id' => $data['customer_id'],
                        ]);

                        Notification::make()
                            ->title('Conversation linked to customer.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('generateSummary')
                    ->label('Generate summary')
                    ->icon('heroicon-o-sparkles')
                    ->requiresConfirmation()
                    ->action(function (Conversation $record) {
                        SyncMessagesJob::dispatch($record, 50);

                        GenerateCustomerSummaryJob::dispatch(
                            conversation: $record,
                            actor: Auth::user(),
                            window: 50,
                        );

                        Notification::make()
                            ->title('AI summary generation started.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConversations::route('/'),
            'view' => Pages\ViewConversation::route('/{record}'),
        ];
    }
}

