<?php

namespace App\Filament\Auth;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Grid::make(3)->schema([

                            Section::make(__('admin.profile.sections.picture'))
                                ->description(__('admin.profile.descriptions.picture'))
                                ->schema([
                                    FileUpload::make('avatar')
                                        ->label(__('admin.profile.fields.avatar'))
                                        ->avatar()
                                        ->imageEditor()
                                        ->circleCropper()
                                        ->disk('public')
                                        ->directory('avatars')
                                        ->visibility('public'),
                                ])
                                ->columnSpan(1),

                            Section::make(__('admin.profile.sections.details'))
                                ->description(__('admin.profile.descriptions.details'))
                                ->schema([
                                    $this->getNameFormComponent(),
                                    $this->getEmailFormComponent(),
                                ])
                                ->columnSpan(2),
                        ]),

                        Section::make(__('admin.profile.sections.security'))
                            ->description(__('admin.profile.descriptions.security'))
                            ->schema([
                                Grid::make(2)->schema([
                                    $this->getPasswordFormComponent(),
                                    $this->getPasswordConfirmationFormComponent(),
                                ]),
                            ]),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data'),
            ),
        ];
    }
}
