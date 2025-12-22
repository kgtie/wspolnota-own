<x-superadmin-layout :pageInfo="['page.title' => 'Kosz Użytkowników', 'meta.description' => 'Kosz']">
    <div class="mb-3">
        <a href="{{ route('superadmin.users.index') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left"></i> Powrót do listy aktywnych
        </a>
    </div>

    <div class="card shadow-sm border-danger">
        <div class="card-header bg-danger text-white">
            <h3 class="card-title"><i class="fa-solid fa-trash-can"></i> Usunięci użytkownicy (Soft Delete)</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Użytkownik</th>
                            <th>Email</th>
                            <th>Data usunięcia</th>
                            <th class="text-end">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->full_name ?? $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->deleted_at->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    <form action="{{ route('superadmin.users.restore', $user->id) }}" method="POST"
                                        class="d-inline-block">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-success" title="Przywróć">
                                            <i class="fa-solid fa-trash-arrow-up"></i> Przywróć
                                        </button>
                                    </form>
                                    <form action="{{ route('superadmin.users.force_delete', $user->id) }}" method="POST"
                                        class="d-inline-block"
                                        onsubmit="return confirm('To operacja nieodwracalna! Użytkownik zostanie całkowicie usunięty.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Usuń trwale">
                                            <i class="fa-solid fa-xmark"></i> Usuń na zawsze
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Kosz jest pusty.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $users->links() }}
        </div>
    </div>
</x-superadmin-layout>