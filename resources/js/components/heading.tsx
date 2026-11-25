export default function Heading({
    title,
    description,
    variant = 'default',
    level = 1,
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
    level?: 1 | 2 | 3;
}) {
    const Tag = `h${level}` as 'h1' | 'h2' | 'h3';

    return (
        <header className={variant === 'small' ? '' : 'mb-8 space-y-0.5'}>
            <Tag
                className={
                    variant === 'small'
                        ? 'mb-0.5 text-base font-medium'
                        : 'text-xl font-semibold tracking-tight'
                }
            >
                {title}
            </Tag>
            {description && (
                <p className="text-sm text-muted-foreground">{description}</p>
            )}
        </header>
    );
}
