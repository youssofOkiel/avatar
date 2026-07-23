import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Level = {
    id: number;
    name: string;
    slug: string;
} | null;

export default function EducationLevelForm({ level }: { level: Level }) {
    const isEdit = level !== null;

    return (
        <>
            <Head title={isEdit ? 'تعديل مرحلة' : 'إضافة مرحلة'} />
            <div className="mx-auto flex w-full max-w-xl flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={isEdit ? 'تعديل مرحلة دراسية' : 'إضافة مرحلة دراسية'}
                    actions={
                        <Button variant="outline" asChild>
                            <Link href="/admin/education-levels">رجوع</Link>
                        </Button>
                    }
                />
                <Form
                    action={
                        isEdit
                            ? `/admin/education-levels/${level.id}`
                            : '/admin/education-levels'
                    }
                    method={isEdit ? 'put' : 'post'}
                    className="space-y-5 rounded-xl border bg-card p-5 shadow-sm"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">اسم المرحلة</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={level?.name ?? ''}
                                    required
                                    autoFocus
                                    placeholder="مثال: الصف الأول الثانوي"
                                />
                                <InputError message={errors.name} />
                            </div>
                            <Button disabled={processing}>
                                {isEdit ? 'حفظ' : 'إضافة'}
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
