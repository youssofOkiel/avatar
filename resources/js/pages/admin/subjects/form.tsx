import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Level = { id: number; name: string };
type LevelGroup = { id: number; name: string; levels: Level[] };

type Subject = {
    id: number;
    name: string;
    education_levels: Level[];
} | null;

export default function SubjectForm({
    subject,
    levelGroups,
}: {
    subject: Subject;
    levelGroups: LevelGroup[];
}) {
    const isEdit = subject !== null;

    const { data, setData, post, put, processing, errors } = useForm<{
        name: string;
        education_level_ids: number[];
    }>({
        name: subject?.name ?? '',
        education_level_ids: subject?.education_levels.map((l) => l.id) ?? [],
    });

    const toggleLevel = (id: number) => {
        setData(
            'education_level_ids',
            data.education_level_ids.includes(id)
                ? data.education_level_ids.filter((x) => x !== id)
                : [...data.education_level_ids, id],
        );
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEdit) {
            put(`/admin/subjects/${subject.id}`);
        } else {
            post('/admin/subjects');
        }
    };

    return (
        <>
            <Head title={isEdit ? 'تعديل مادة' : 'إضافة مادة'} />
            <div className="mx-auto flex w-full max-w-xl flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={isEdit ? 'تعديل مادة' : 'إضافة مادة'}
                    actions={
                        <Button variant="outline" asChild>
                            <Link href="/admin/subjects">رجوع</Link>
                        </Button>
                    }
                />
                <form
                    onSubmit={submit}
                    className="space-y-5 rounded-xl border bg-card p-5 shadow-sm"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">اسم المادة</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                            autoFocus
                            placeholder="مثال: الرياضيات"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label>المراحل الدراسية</Label>
                        {levelGroups.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                لا توجد مراحل دراسية مُعرَّفة في النظام.
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {levelGroups.map((group) => (
                                    <div key={group.id} className="space-y-2">
                                        <div className="text-sm font-semibold text-primary">
                                            {group.name}
                                        </div>
                                        <div className="grid gap-2 sm:grid-cols-2">
                                            {group.levels.map((level) => (
                                                <label
                                                    key={level.id}
                                                    className="flex cursor-pointer items-center gap-2 rounded-md border bg-background p-2 text-sm"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        className="size-4"
                                                        checked={data.education_level_ids.includes(
                                                            level.id,
                                                        )}
                                                        onChange={() =>
                                                            toggleLevel(
                                                                level.id,
                                                            )
                                                        }
                                                    />
                                                    {level.name}
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                        <InputError message={errors.education_level_ids} />
                    </div>

                    <Button disabled={processing}>
                        {isEdit ? 'حفظ' : 'إضافة'}
                    </Button>
                </form>
            </div>
        </>
    );
}
