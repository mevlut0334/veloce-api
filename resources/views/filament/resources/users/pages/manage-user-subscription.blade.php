<x-filament-panels::page>
    <div class="space-y-3">
        {{-- Mevcut Abonelik Durumu --}}
        @if($record->isSubscriber())
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                Aktif Abonelik: {{ $record->activeSubscription->plan->name }}
                            </h3>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                            Aktif
                        </span>
                    </div>
                </div>

                {{-- Tablo --}}
                <div class="p-3">
                    <table class="w-full text-xs mb-3">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-5 py-1.5 text-left font-semibold text-gray-600 dark:text-gray-400">Başlangıç</th>
                                <th class="px-5 py-1.5 text-left font-semibold text-gray-600 dark:text-gray-400">Bitiş</th>
                                <th class="px-5 py-1.5 text-left font-semibold text-gray-600 dark:text-gray-400">Kalan Süre</th>
                                <th class="px-5 py-1.5 text-left font-semibold text-gray-600 dark:text-gray-400">İlerleme</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-t border-gray-200 dark:border-gray-700">
                                <td class="px-5 py-2 font-medium text-gray-900 dark:text-white">
                                    {{ $record->activeSubscription->starts_at->format('d.m.Y H:i') }}
                                </td>
                                <td class="px-5 py-2 font-medium text-gray-900 dark:text-white">
                                    {{ $record->activeSubscription->expires_at->format('d.m.Y H:i') }}
                                </td>
                                <td class="px-5 py-2 font-bold text-blue-700 dark:text-blue-300">
                                    {{ $record->activeSubscription->remainingDays() }} gün ({{ $record->activeSubscription->remainingHours() }} saat)
                                </td>
                                <td class="px-5 py-2">
                                    <div class="flex items-center space-x-2">
                                        <div class="flex-1 bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                                            <div class="bg-gradient-to-r from-green-500 to-emerald-500 h-1.5 rounded-full"
                                                 style="width: {{ $record->activeSubscription->getProgressPercentage() }}%">
                                            </div>
                                        </div>
                                        <span class="text-xs font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                                            {{ $record->activeSubscription->getProgressPercentage() }}%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- Detaylar --}}
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-5 py-1.5 text-left font-semibold text-gray-600 dark:text-gray-400">Abonelik Tipi</th>
                                @if($record->activeSubscription->payment_method)
                                <th class="px-5 py-1.5 text-left font-semibold text-gray-600 dark:text-gray-400">Ödeme Yöntemi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-t border-gray-200 dark:border-gray-700">
                                <td class="px-5 py-2 font-medium text-gray-900 dark:text-white">
                                    @if($record->activeSubscription->subscription_type === 'manual')
                                        Manuel (Admin)
                                    @elseif($record->activeSubscription->subscription_type === 'paid')
                                        Ödeme Yapıldı
                                    @else
                                        Deneme Sürümü
                                    @endif
                                </td>
                                @if($record->activeSubscription->payment_method)
                                <td class="px-5 py-2 font-medium text-gray-900 dark:text-white capitalize">
                                    {{ str_replace('_', ' ', $record->activeSubscription->payment_method) }}
                                </td>
                                @endif
                            </tr>
                        </tbody>
                    </table>

                    {{-- Admin Notu --}}
                    @if($record->activeSubscription->admin_note)
                    <div class="mt-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded border border-blue-200 dark:border-blue-800">
                        <p class="text-xs font-semibold text-blue-700 dark:text-blue-400 mb-1">Admin Notu:</p>
                        <p class="text-xs text-blue-900 dark:text-blue-300 whitespace-pre-line">{{ $record->activeSubscription->admin_note }}</p>
                    </div>
                    @endif
                </div>
            </div>
        @else
            {{-- Abonelik Yok --}}
            <div class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800 p-3">
                <h3 class="text-sm font-semibold text-yellow-900 dark:text-yellow-200 mb-1">
                    Aktif Abonelik Yok
                </h3>
                <p class="text-xs text-yellow-700 dark:text-yellow-300">
                    "Yeni Abonelik Oluştur" butonunu kullanarak abonelik oluşturabilirsiniz.
                </p>
            </div>
        @endif

        {{-- Abonelik Geçmişi --}}
        @if($record->userSubscriptions->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-2 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                    Abonelik Geçmişi
                </h3>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                    {{ $record->userSubscriptions->count() }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-5 py-1.5 text-left font-semibold text-gray-600 dark:text-gray-400">Plan</th>
                            <th class="px-5 py-1.5 text-left font-semibold text-gray-600 dark:text-gray-400">Başlangıç</th>
                            <th class="px-5 py-1.5 text-left font-semibold text-gray-600 dark:text-gray-400">Bitiş</th>
                            <th class="px-5 py-1.5 text-left font-semibold text-gray-600 dark:text-gray-400">Durum</th>
                            <th class="px-5 py-1.5 text-left font-semibold text-gray-600 dark:text-gray-400">Tip</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($record->userSubscriptions->sortByDesc('created_at') as $subscription)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                            <td class="px-5 py-1.5 text-gray-900 dark:text-white font-medium">
                                {{ $subscription->plan->name }}
                            </td>
                            <td class="px-5 py-1.5 text-gray-600 dark:text-gray-400">
                                {{ $subscription->starts_at->format('d.m.Y') }}
                            </td>
                            <td class="px-5 py-1.5 text-gray-600 dark:text-gray-400">
                                {{ $subscription->expires_at->format('d.m.Y') }}
                            </td>
                            <td class="px-5 py-1.5">
                                @if($subscription->status === 'active')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300">
                                        Aktif
                                    </span>
                                @elseif($subscription->status === 'expired')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                                        Dolmuş
                                    </span>
                                @elseif($subscription->status === 'cancelled')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        İptal
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300">
                                        Bekliyor
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-1.5 text-gray-600 dark:text-gray-400">
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
