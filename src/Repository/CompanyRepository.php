<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    /** @return Company[] */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForUser(int $id, User $user): ?Company
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->andWhere('c.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param array{name?: string, role?: int, contact?: string} $filters
     *
     * @return array{items: Company[], total: int}
     */
    public function findForUserWithFilters(User $user, array $filters, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.companyRole', 'r')
            ->addSelect('r')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user);

        if (!empty($filters['name'])) {
            $name = '%'.strtolower($filters['name']).'%';
            $qb->andWhere('LOWER(c.name) LIKE :name')
                ->setParameter('name', $name);
        }

        if (!empty($filters['contact'])) {
            $contact = '%'.strtolower($filters['contact']).'%';
            $qb->andWhere('LOWER(c.contact) LIKE :contact')
                ->setParameter('contact', $contact);
        }

        if (!empty($filters['role'])) {
            $qb->andWhere('r.id = :role')
                ->setParameter('role', (int) $filters['role']);
        }

        $qb->orderBy('c.name', 'ASC');

        $query = $qb->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($query);

        return [
            'items' => iterator_to_array($paginator),
            'total' => $paginator->count(),
        ];
    }
}
