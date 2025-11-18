<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Mevcut Abonelik Durumu --}}
        @if($record->isSubscriber())
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Aktif Abonelik
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $record->activeSubscription->plan->name }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Aktif
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                    {{-- Başlangıç Tarihi --}}
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Başlangıç Tarihi</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $record->activeSubscription->starts_at->format('d.m.Y H:i') }}
                        </p>
                    </div>

                    {{-- Bitiş Tarihi --}}
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Bitiş Tarihi</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $record->activeSubscription->expires_at->format('d.m.Y H:i') }}
                        </p>
                    </div>

                    {{-- Kalan Süre --}}
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Kalan Süre</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $record->activeSubscription->remainingDays() }} gün
                            ({{ $record->activeSubscription->remainingHours() }} saat)
                        </p>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="mt-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Abonelik İlerlemesi</span>
                        <span class="text-xs font-medium text-gray-900 dark:text-white">
                            {{ $record->activeSubscription->getProgressPercentage() }}%
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="bg-green-600 h-2 rounded-full transition-all duration-300"
                             style="width: {{ $record->activeSubscription->getProgressPercentage() }}%">
                        </div>
                    </div>
                </div>

                {{-- Ek Bilgiler --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Abonelik Tipi</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            @if($record->activeSubscription->subscription_type === 'manual')
                                <span class="inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                    </svg>
                                    Manuel (Admin)
                                </span>
                            @elseif($record->activeSubscription->subscription_type === 'paid')
                                <span class="inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                        <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                                    </svg>
                                    Ödeme Yapıldı
                                </span>
                            @else
                                <span class="inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1.5 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                    Deneme
                                </span>
                            @endif
                        </p>
                    </div>

                    @if($record->activeSubscription->payment_method)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Ödeme Yöntemi</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white capitalize">
                            {{ str_replace('_', ' ', $record->activeSubscription->payment_method) }}
                        </p>
                    </div>
                    @endif
                </div>

                {{-- Admin Notu --}}
                @if($record->activeSubscription->admin_note)
                <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <p class="text-xs text-blue-600 dark:text-blue-400 font-medium mb-1">Admin Notu:</p>
                    <p class="text-sm text-blue-900 dark:text-blue-300 whitespace-pre-line">{{ $record->activeSubscription->admin_note }}</p>
                </div>
                @endif
            </div>
        @else
            {{-- Abonelik Yok --}}
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800 p-6">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h3 class="text-lg font-semibold text-yellow-900 dark:text-yellow-200">
                            Aktif Abonelik Bulunmuyor
                        </h3>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                            Bu kullanıcının aktif bir aboneliği yok. Yukarıdaki "Yeni Abonelik Oluştur" butonunu kullanarak abonelik oluşturabilirsiniz.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Abonelik Geçmişi --}}
        @if($record->userSubscriptions->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Abonelik Geçmişi
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Plan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Başlangıç</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bitiş</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Durum</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tip</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($record->userSubscriptions->sortByDesc('created_at') as $subscription)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">
                                {{ $subscription->plan->name }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                {{ $subscription->starts_at->format('d.m.Y') }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                {{ $subscription->expires_at->format('d.m.Y') }}
                            </td>
                            <td class="px-4 py-3">
                                @if($subscription->status === 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Aktif
                                    </span>
                                @elseif($subscription->status === 'expired')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        Süresi Dolmuş
                                    </span>
                                @elseif($subscription->status === 'cancelled')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        İptal Edildi
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        Beklemede
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 capitalize">
                                {{ $subscription->subscription_type === 'manual' ? 'Manuel' : ($subscription->subscription_type === 'paid' ? 'Ödeme' : 'Deneme') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>
