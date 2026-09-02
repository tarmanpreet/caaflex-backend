const roleLabels = {
    superadmin: 'Super amministratore',
    admin: 'Amministratore',
    employee: 'Operatore',
    cliente: 'Cliente',
};

export const roleLabel = (role) => roleLabels[role] ?? role;
