import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Student = {
    id: number;
    name: string | null;
    phone: string | null;
} | null;

export default function StudentForm({ student }: { student: Student }) {
    const isEdit = student !== null;

    return (
        <>
            <Head title={isEdit ? 'تعديل طالب' : 'إضافة طالب'} />
            <div className="mx-auto flex w-full max-w-xl flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={isEdit ? 'تعديل طالب' : 'إضافة طالب'}
                    description="يكفي إدخال الاسم أو رقم الهاتف على الأقل."
                    actions={
                        <Button variant="outline" asChild>
                            <Link href="/admin/students">رجوع</Link>
                        </Button>
                    }
                />
                <Form
                    action={
                        isEdit ? `/admin/students/${student.id}` : '/admin/students'
                    }
                    method={isEdit ? 'put' : 'post'}
                    className="space-y-5 rounded-xl border bg-card p-5 shadow-sm"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">الاسم</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={student?.name ?? ''}
                                    autoFocus
                                    autoComplete="name"
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="phone">رقم الهاتف</Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    inputMode="tel"
                                    className="dir-ltr text-start"
                                    defaultValue={student?.phone ?? ''}
                                    autoComplete="tel"
                                />
                                <InputError message={errors.phone} />
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
